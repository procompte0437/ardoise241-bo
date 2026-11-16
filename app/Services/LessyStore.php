<?php

namespace App\Services;

use App\Models\Food;
use App\Models\LedgerEntry;
use App\Models\LedgerItem;
use App\Models\Person;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LessyStore
{
    public const MAX_NAMES = 200;

    public const MAX_FOODS = 150;

    public const LOCK_CODE = '011110';

    public const DEFAULT_CODE = '0110';

    public function ensureInstalled(): void
    {
        $this->seed();
    }

    public function seed(): void
    {
        if (Setting::query()->where('key', 'admin_code')->exists()) {
            return;
        }

        DB::transaction(function () {
            Setting::putValue('admin_code', self::DEFAULT_CODE);
            Setting::putValue('install_date', (string) now()->getTimestampMs());
            Setting::putValue('unlocked', '0');

            $now = now();
            $people = [];
            for ($i = 1; $i <= self::MAX_NAMES; $i++) {
                $people[] = [
                    'name' => 'Nom '.$i,
                    'debt' => 0,
                    'position' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($people, 50) as $chunk) {
                Person::query()->insert($chunk);
            }

            $foods = [];
            for ($j = 1; $j <= self::MAX_FOODS; $j++) {
                $foods[] = [
                    'name' => 'Image '.$j,
                    'price' => 0,
                    'image' => null,
                    'position' => $j,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($foods, 50) as $chunk) {
                Food::query()->insert($chunk);
            }
        });
    }

    public static function format(int|float|string|null $n): string
    {
        return number_format((int) $n, 0, ',', ' ').' F';
    }

    public function adminCode(): string
    {
        return (string) Setting::getValue('admin_code', self::DEFAULT_CODE);
    }

    public function checkAdmin(string $code): bool
    {
        return hash_equals($this->adminCode(), $code);
    }

    public function changeCode(string $code): void
    {
        if (! preg_match('/^\d{4}$/', $code)) {
            throw new RuntimeException('Le code doit avoir exactement 4 chiffres.');
        }

        Setting::putValue('admin_code', $code);
    }

    public function isLocked(): bool
    {
        if (Setting::getValue('unlocked') === '1') {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->installAt()->copy()->addMonths(3));
    }

    public function unlock(string $code): bool
    {
        if (! hash_equals(self::LOCK_CODE, $code)) {
            return false;
        }

        Setting::putValue('unlocked', '1');

        return true;
    }

    public function issueAdminToken(): string
    {
        $token = bin2hex(random_bytes(24));
        Cache::put($this->tokenKey($token), true, now()->addHours(12));

        return $token;
    }

    public function adminTokenValid(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return Cache::get($this->tokenKey($token)) === true;
    }

    public function forgetAdminToken(?string $token): void
    {
        if ($token) {
            Cache::forget($this->tokenKey($token));
        }
    }

    /**
     * @return array<int, array{id:int,name:string,debt:int,num:int}>
     */
    public function peoplePayload(): array
    {
        return Person::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name', 'debt'])
            ->values()
            ->map(fn (Person $person, int $index) => [
                'id' => $person->id,
                'name' => $person->name,
                'debt' => (int) $person->debt,
                'num' => $index + 1,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,price:int,img:string}>
     */
    public function foodsPayload(): array
    {
        return Food::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Food $food) => $this->foodPayload($food))
            ->all();
    }

    /**
     * @return array{id:int,name:string,price:int,img:string}
     */
    public function foodPayload(Food $food): array
    {
        return [
            'id' => $food->id,
            'name' => $food->name,
            'price' => (int) $food->price,
            'img' => $food->imageUrl(),
        ];
    }

    /**
     * @return array{id:int,name:string,debt:int,history:array<int, array<string, mixed>>}
     */
    public function personPayload(Person $person): array
    {
        $person->load('entries.items');

        return [
            'id' => $person->id,
            'name' => $person->name,
            'debt' => (int) $person->debt,
            'history' => $person->entries->map(function (LedgerEntry $entry) {
                return [
                    't' => $entry->happened_at->getTimestamp() * 1000,
                    'amt' => (int) $entry->amount,
                    'pay' => (bool) $entry->pay,
                    'label' => $entry->label,
                    'items' => $entry->items->map(fn (LedgerItem $item) => [
                        'n' => $item->name,
                        'q' => (int) $item->qty,
                        'p' => (int) $item->price,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function addNames(string $text): int
    {
        $lines = array_values(array_filter(array_map(
            fn (string $line) => trim($line),
            preg_split("/\r\n|\n|\r/", $text) ?: []
        ), fn (string $line) => $line !== ''));

        if ($lines === []) {
            throw new RuntimeException('Écrivez au moins un nom.');
        }

        $existing = Person::query()->pluck('name')->map(fn ($name) => mb_strtolower($name))->all();
        $known = array_fill_keys($existing, true);
        $room = self::MAX_NAMES - Person::query()->count();
        $added = 0;
        $next = (int) Person::query()->max('position') + 1;
        $now = now();
        $rows = [];

        foreach ($lines as $line) {
            if ($added >= $room) {
                break;
            }
            $name = mb_substr($line, 0, 40);
            $key = mb_strtolower($name);
            if (isset($known[$key])) {
                continue;
            }
            $known[$key] = true;
            $rows[] = [
                'name' => $name,
                'debt' => 0,
                'position' => $next++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $added++;
        }

        if ($rows !== []) {
            Person::query()->insert($rows);
        }

        return $added;
    }

    public function rename(Person $person, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Le nom ne peut pas être vide.');
        }

        $person->name = mb_substr($name, 0, 40);
        $person->save();
    }

    /**
     * @param  array<string|int, mixed>  $counts
     * @return array{amount:int,person:array<string,mixed>}
     */
    public function addSession(Person $person, array $counts): array
    {
        $foods = Food::query()->get()->keyBy('id');
        $items = [];
        $total = 0;

        foreach ($counts as $id => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0 || ! $foods->has((int) $id)) {
                continue;
            }
            $qty = min($qty, 100000);
            $food = $foods->get((int) $id);
            $line = $qty * (int) $food->price;
            $total += $line;
            $items[] = [
                'name' => $food->name,
                'qty' => $qty,
                'price' => (int) $food->price,
            ];
        }

        if ($total <= 0) {
            throw new RuntimeException('Touchez des images pour ajouter des montants.');
        }

        DB::transaction(function () use ($person, $total, $items) {
            $person->debt = (int) $person->debt + $total;
            $person->save();

            $entry = LedgerEntry::query()->create([
                'person_id' => $person->id,
                'amount' => $total,
                'pay' => false,
                'label' => null,
                'happened_at' => now(),
            ]);

            foreach ($items as $item) {
                if ($item['qty'] <= 0) {
                    continue;
                }
                LedgerItem::query()->create([
                    'ledger_entry_id' => $entry->id,
                    'name' => $item['name'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }
        });

        return [
            'amount' => $total,
            'person' => $this->personPayload($person->fresh()),
        ];
    }

    /**
     * @return array{id:int,name:string,debt:int,history:array<int,mixed>}
     */
    public function pay(Person $person, int $amount): array
    {
        if ($amount <= 0) {
            throw new RuntimeException('Entrez un montant supérieur à 0.');
        }

        DB::transaction(function () use ($person, $amount) {
            $person->debt = max(0, (int) $person->debt - $amount);
            $person->save();
            LedgerEntry::query()->create([
                'person_id' => $person->id,
                'amount' => $amount,
                'pay' => true,
                'label' => 'Paiement',
                'happened_at' => now(),
            ]);
        });

        return $this->personPayload($person->fresh());
    }

    /**
     * @return array{id:int,name:string,debt:int,history:array<int,mixed>}
     */
    public function erase(Person $person, string $code): array
    {
        if (! $this->checkAdmin($code)) {
            throw new RuntimeException('Code incorrect. Le bilan reste inchangé.');
        }

        DB::transaction(function () use ($person) {
            $ids = $person->entries()->pluck('id');
            LedgerItem::query()->whereIn('ledger_entry_id', $ids)->delete();
            $person->entries()->delete();
            $person->debt = 0;
            $person->save();
        });

        return $this->personPayload($person->fresh());
    }

    public function addFood(): Food
    {
        if (Food::query()->count() >= self::MAX_FOODS) {
            throw new RuntimeException('Maximum '.self::MAX_FOODS.' aliments.');
        }

        return Food::query()->create([
            'name' => 'Nouvel aliment',
            'price' => 0,
            'image' => null,
            'position' => (int) Food::query()->max('position') + 1,
        ]);
    }

    public function updateFood(Food $food, ?string $name, mixed $price): void
    {
        if ($name !== null) {
            $food->name = mb_substr(trim($name), 0, 30);
        }
        if ($price !== null && $price !== '') {
            $food->price = max(0, (int) round((float) $price));
        }
        $food->save();
    }

    public function deleteFood(Food $food): void
    {
        if ($food->image) {
            Storage::disk('public')->delete($food->image);
        }
        $food->delete();
    }

    public function storeImage(Food $food, UploadedFile $file): void
    {
        $binary = $this->shrink($file->getPathname());
        if ($binary === null) {
            throw new RuntimeException('Image illisible.');
        }

        $relative = 'foods/'.$food->id.'.jpg';
        Storage::disk('public')->put($relative, $binary);
        $food->image = $relative;
        $food->save();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addPhotos(array $files): int
    {
        $empty = Food::query()->whereNull('image')->orderBy('position')->orderBy('id')->get();
        $room = $empty->count() + (self::MAX_FOODS - Food::query()->count());
        if ($room <= 0) {
            throw new RuntimeException('Toutes les places sont prises. Touchez une photo pour la changer.');
        }

        $files = array_slice($files, 0, $room);
        $ok = 0;
        $slotIndex = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            try {
                $binary = $this->shrink($file->getPathname());
            } catch (\Throwable) {
                $binary = null;
            }
            if ($binary === null) {
                continue;
            }

            if ($slotIndex < $empty->count()) {
                $food = $empty[$slotIndex];
                $slotIndex++;
            } else {
                try {
                    $food = $this->addFood();
                } catch (RuntimeException) {
                    break;
                }
            }

            $relative = 'foods/'.$food->id.'.jpg';
            Storage::disk('public')->put($relative, $binary);
            $food->image = $relative;
            $food->save();
            $ok++;
        }

        return $ok;
    }

    public function whenLabel(Carbon $date): string
    {
        return $date->locale('fr')->isoFormat('D MMM · HH:mm');
    }

    private function installAt(): Carbon
    {
        $raw = Setting::getValue('install_date');
        if (! $raw) {
            return now();
        }

        return Carbon::createFromTimestampMs((int) $raw);
    }

    private function tokenKey(string $token): string
    {
        return 'lessy_admin:'.$token;
    }

    private function shrink(string $path): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return $raw;
        }

        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);

            return null;
        }

        $size = 180;
        $side = min($width, $height);
        $dst = imagecreatetruecolor($size, $size);
        $background = imagecolorallocate($dst, 243, 215, 122);
        imagefilledrectangle($dst, 0, 0, $size, $size, $background);
        imagecopyresampled(
            $dst,
            $src,
            0,
            0,
            (int) (($width - $side) / 2),
            (int) (($height - $side) / 2),
            $size,
            $size,
            $side,
            $side
        );

        ob_start();
        $written = imagejpeg($dst, null, 72);
        $binary = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        if (! $written || ! is_string($binary) || $binary === '') {
            return null;
        }

        return $binary;
    }
}
