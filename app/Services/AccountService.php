<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountEntry;
use App\Models\AccountFood;
use App\Models\AccountPerson;
use App\Models\AccountToken;
use App\Models\Setting;
use App\Support\Thumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Comptes de l'application mobile et synchronisation de leurs données.
 *
 * Le téléphone travaille hors ligne. À chaque connexion il envoie ce qu'il a
 * modifié (lignes « sales ») et récupère tout ce dont la révision dépasse son
 * curseur. En cas de conflit, la modification la plus récente gagne
 * (client_updated_at, en millisecondes).
 */
class AccountService
{
    public const DEFAULT_TRIAL_DAYS = 90;

    public const MAX_NAMES = 200;

    public const MAX_FOODS = 150;

    /** « Client » réservé par l'application aux ventes encaissées tout de suite. */
    public const CASH = '_caisse';

    /**
     * Fiche d'inventaire d'un compte depuis une date (ms), comme sur le téléphone.
     *
     * @return array{cash:int, cashCount:int, credit:int, payments:int, received:int, articles:array}
     */
    public function inventory(Account $account, int $from = 0): array
    {
        $out = ['cash' => 0, 'cashCount' => 0, 'credit' => 0, 'payments' => 0, 'articles' => []];
        AccountEntry::query()->where('account_id', $account->id)->where('deleted', false)
            ->where('happened_at', '>=', $from)
            ->each(function (AccountEntry $e) use (&$out) {
                if ($e->pay) {
                    $out['payments'] += $e->amount;

                    return;
                }
                if ($e->person_uid === self::CASH) {
                    $out['cash'] += $e->amount;
                    $out['cashCount']++;
                } else {
                    $out['credit'] += $e->amount;
                }
                foreach ($e->items ?? [] as $it) {
                    $name = (string) ($it['n'] ?? '');
                    $out['articles'][$name] ??= ['n' => $name, 'q' => 0, 'amount' => 0];
                    $out['articles'][$name]['q'] += (int) ($it['q'] ?? 0);
                    $out['articles'][$name]['amount'] += (int) ($it['q'] ?? 0) * (int) ($it['p'] ?? 0);
                }
            });
        usort($out['articles'], fn ($a, $b) => $b['amount'] <=> $a['amount']);
        $out['received'] = $out['cash'] + $out['payments'];

        return $out;
    }

    public static function globalTrialDays(): int
    {
        return max(0, (int) Setting::getValue('trial_days_default', (string) self::DEFAULT_TRIAL_DAYS));
    }

    public static function setGlobalTrialDays(int $days): void
    {
        Setting::putValue('trial_days_default', (string) max(0, min(3650, $days)));
    }

    /**
     * Code admin Ardoise241 : il ouvre le mode gérant sur n'importe quel téléphone
     * (dépannage). Le téléphone reçoit seulement son empreinte, pour vérifier sans réseau.
     * Tant qu'il n'a pas été réglé, on reprend le code admin de l'ancienne version.
     */
    public static function supportLocal(): array
    {
        $salt = Setting::getValue('support_salt');
        $hash = Setting::getValue('support_hash');
        if (! $salt || ! $hash) {
            $legacy = (string) Setting::getValue('admin_code', LessyStore::DEFAULT_CODE);
            self::setSupportCode($legacy);
            $salt = Setting::getValue('support_salt');
            $hash = Setting::getValue('support_hash');
        }

        return ['salt' => $salt, 'hash' => $hash];
    }

    public static function setSupportCode(string $code): void
    {
        self::assertCode($code);
        $salt = Str::random(24);
        Setting::putValue('support_salt', $salt);
        Setting::putValue('support_hash', hash('sha256', $salt.$code));
    }

    /** Garde les chiffres (et un + de tête) : « 077 12 34 56 » devient « 077123456 ». */
    public static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $plus = str_starts_with($phone, '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $plus.$digits;
    }

    public static function assertPhone(string $phone): void
    {
        $digits = ltrim($phone, '+');
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new RuntimeException('Numéro de téléphone invalide.');
        }
    }

    public static function assertCode(string $code): void
    {
        if (! preg_match('/^\d{4,6}$/', $code)) {
            throw new RuntimeException('Le code doit avoir 4 à 6 chiffres.');
        }
    }

    public function register(string $phone, string $code, ?string $name, ?string $device): array
    {
        $phone = self::normalizePhone($phone);
        self::assertPhone($phone);
        self::assertCode($code);

        if (Account::query()->where('phone', $phone)->exists()) {
            throw new RuntimeException('Ce numéro a déjà un compte. Touchez « J\'ai déjà un compte » pour le retrouver.');
        }

        $account = new Account([
            'phone' => $phone,
            'name' => $name !== null && trim($name) !== '' ? Str::limit(trim($name), 60, '') : null,
            'trial_started_at' => now(), // le compte à rebours démarre à la création du code
            'last_device' => $device ? Str::limit($device, 120, '') : null,
        ]);
        $this->fillCode($account, $code);
        $account->save();
        $account->refresh();

        return [$account, $this->issueToken($account, $device)];
    }

    public function login(string $phone, string $code, ?string $device): array
    {
        $phone = self::normalizePhone($phone);
        $account = Account::query()->where('phone', $phone)->first();

        if (! $account || ! Hash::check($code, $account->code_hash)) {
            throw new RuntimeException('Numéro ou code incorrect.');
        }
        if ($account->blocked) {
            throw new RuntimeException('Ce compte est suspendu. Contactez le support.');
        }

        $account->last_device = $device ? Str::limit($device, 120, '') : $account->last_device;
        $account->save();

        return [$account, $this->issueToken($account, $device)];
    }

    public function setCode(Account $account, string $code): void
    {
        self::assertCode($code);
        $this->fillCode($account, $code);
        $account->save();
    }

    public function resolveToken(?string $token): ?Account
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $row = AccountToken::query()->where('token_hash', hash('sha256', $token))->first();
        if (! $row) {
            return null;
        }

        if (! $row->last_used_at || $row->last_used_at->lt(now()->subMinutes(5))) {
            $row->forceFill(['last_used_at' => now()])->save();
        }

        return $row->account;
    }

    public function forgetToken(string $token): void
    {
        AccountToken::query()->where('token_hash', hash('sha256', $token))->delete();
    }

    /** Ce que le téléphone garde de son compte (aussi hors ligne). */
    public function payload(Account $account): array
    {
        return [
            'phone' => $account->phone,
            'name' => $account->name,
            'status' => $account->status(),
            'blocked' => $account->blocked,
            'expired' => $account->isExpired(),
            'endsAt' => $account->accessEndsAt()->getTimestampMs(),
            'daysLeft' => $account->daysLeft(),
            'serverTime' => now()->getTimestampMs(),
            // Permet de vérifier le code sur le téléphone sans réseau.
            'local' => ['salt' => $account->local_salt, 'hash' => $account->local_hash],
            'support' => self::supportLocal(),
        ];
    }

    /**
     * @param  array{people?: array, foods?: array, entries?: array}  $changes
     */
    public function sync(Account $account, array $changes, int $since, ?string $device): array
    {
        DB::transaction(function () use ($account, $changes, $device) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);
            $rev = $locked->rev;

            foreach (array_slice($changes['people'] ?? [], 0, 1000) as $row) {
                $uid = $this->uid($row);
                if ($uid === null) {
                    continue;
                }
                $name = Str::limit(trim((string) ($row['name'] ?? '')), 40, '');
                $this->upsert(AccountPerson::class, $locked, $uid, $row, $rev, [
                    'name' => $name !== '' ? $name : 'Nom',
                    'position' => max(0, (int) ($row['position'] ?? 0)),
                ]);
            }

            foreach (array_slice($changes['foods'] ?? [], 0, 1000) as $row) {
                $uid = $this->uid($row);
                if ($uid === null) {
                    continue;
                }
                $name = Str::limit(trim((string) ($row['name'] ?? '')), 40, '');
                $this->upsert(AccountFood::class, $locked, $uid, $row, $rev, [
                    'name' => $name !== '' ? $name : 'Image',
                    'price' => max(0, min(100_000_000, (int) ($row['price'] ?? 0))),
                    'position' => max(0, (int) ($row['position'] ?? 0)),
                ]);
            }

            foreach (array_slice($changes['entries'] ?? [], 0, 5000) as $row) {
                $uid = $this->uid($row);
                $person = $this->uid(['uid' => $row['person'] ?? null]);
                if ($uid === null || $person === null) {
                    continue;
                }
                $items = [];
                foreach (array_slice(is_array($row['items'] ?? null) ? $row['items'] : [], 0, self::MAX_FOODS) as $item) {
                    $items[] = [
                        'n' => Str::limit((string) ($item['n'] ?? ''), 40, ''),
                        'q' => max(0, (int) ($item['q'] ?? 0)),
                        'p' => max(0, (int) ($item['p'] ?? 0)),
                    ];
                }
                $this->upsert(AccountEntry::class, $locked, $uid, $row, $rev, [
                    'person_uid' => $person,
                    'amount' => max(0, min(1_000_000_000, (int) ($row['amount'] ?? 0))),
                    'pay' => (bool) ($row['pay'] ?? false),
                    'label' => isset($row['label']) ? Str::limit((string) $row['label'], 60, '') : null,
                    'items' => $items,
                    'happened_at' => max(0, (int) ($row['at'] ?? 0)),
                ]);
            }

            $locked->rev = $rev;
            $locked->last_sync_at = now();
            if ($device) {
                $locked->last_device = Str::limit($device, 120, '');
            }
            $locked->save();
        });

        $account->refresh();

        return [
            'cursor' => $account->rev,
            'account' => $this->payload($account),
            'people' => AccountPerson::query()->where('account_id', $account->id)->where('rev', '>', $since)->get()
                ->map(fn (AccountPerson $p) => [
                    'uid' => $p->uid, 'name' => $p->name, 'position' => $p->position,
                    'deleted' => $p->deleted, 'updatedAt' => $p->client_updated_at,
                ])->values(),
            'foods' => AccountFood::query()->where('account_id', $account->id)->where('rev', '>', $since)->get()
                ->map(fn (AccountFood $f) => [
                    'uid' => $f->uid, 'name' => $f->name, 'price' => $f->price, 'position' => $f->position,
                    'img' => $f->image_hash, 'deleted' => $f->deleted, 'updatedAt' => $f->client_updated_at,
                ])->values(),
            'entries' => AccountEntry::query()->where('account_id', $account->id)->where('rev', '>', $since)->get()
                ->map(fn (AccountEntry $e) => [
                    'uid' => $e->uid, 'person' => $e->person_uid, 'amount' => $e->amount, 'pay' => $e->pay,
                    'label' => $e->label, 'items' => $e->items ?? [], 'at' => $e->happened_at,
                    'deleted' => $e->deleted, 'updatedAt' => $e->client_updated_at,
                ])->values(),
        ];
    }

    public function storeImage(Account $account, string $uid, UploadedFile $file): AccountFood
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,40}$/', $uid)) {
            throw new RuntimeException('Identifiant d\'image invalide.');
        }

        $binary = Thumbnail::make($file->getPathname());
        if ($binary === null) {
            throw new RuntimeException('Image illisible.');
        }

        return DB::transaction(function () use ($account, $uid, $binary) {
            $locked = Account::query()->lockForUpdate()->findOrFail($account->id);
            $food = AccountFood::query()->firstOrNew(['account_id' => $locked->id, 'uid' => $uid], [
                'name' => 'Image', 'price' => 0, 'position' => 0, 'client_updated_at' => 0,
            ]);
            $food->image_data = base64_encode($binary);
            $food->image_hash = sha1($binary);
            $food->rev = ++$locked->rev;
            $food->save();
            $locked->save();

            return $food;
        });
    }

    /**
     * Bilan d'un compte, calculé comme sur le téléphone : un trop-payé ramène la
     * dette à 0, jamais en dessous.
     *
     * @return array<string, int> dette par uid de client
     */
    public function debts(Account $account): array
    {
        $debts = [];
        AccountEntry::query()->where('account_id', $account->id)->where('deleted', false)
            ->where('person_uid', '!=', self::CASH) // ventes encaissées : pas une dette
            ->orderBy('happened_at')->orderBy('id')
            ->each(function (AccountEntry $e) use (&$debts) {
                $current = $debts[$e->person_uid] ?? 0;
                $debts[$e->person_uid] = $e->pay ? max(0, $current - $e->amount) : $current + $e->amount;
            });

        return $debts;
    }

    private function fillCode(Account $account, string $code): void
    {
        $salt = Str::random(24);
        $account->code_hash = Hash::make($code);
        $account->local_salt = $salt;
        $account->local_hash = hash('sha256', $salt.$code);
    }

    private function issueToken(Account $account, ?string $device): string
    {
        $token = Str::random(48);
        $account->tokens()->create([
            'token_hash' => hash('sha256', $token),
            'device' => $device ? Str::limit($device, 120, '') : null,
            'last_used_at' => now(),
        ]);

        return $token;
    }

    private function uid(array $row): ?string
    {
        $uid = (string) ($row['uid'] ?? '');

        return preg_match('/^[A-Za-z0-9_-]{1,40}$/', $uid) ? $uid : null;
    }

    /** Applique une ligne venue du téléphone si elle est plus récente que celle du serveur. */
    private function upsert(string $model, Account $account, string $uid, array $row, int &$rev, array $values): void
    {
        $updatedAt = max(0, (int) ($row['updatedAt'] ?? 0));
        $record = $model::query()->firstOrNew(['account_id' => $account->id, 'uid' => $uid]);

        if ($record->exists && $record->client_updated_at > $updatedAt) {
            return; // le serveur a une version plus récente : il la renverra au téléphone
        }

        $record->fill($values);
        $record->deleted = (bool) ($row['deleted'] ?? false);
        $record->client_updated_at = $updatedAt;
        $record->rev = ++$rev;
        $record->save();
    }
}
