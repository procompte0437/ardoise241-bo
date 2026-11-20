<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountEntry;
use App\Models\AccountFood;
use App\Models\AccountPerson;
use App\Models\ShopLink;
use App\Models\ShopMessage;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Espace client : boutiques suivies, commandes, bons et messages.
 *
 * Un client qui suit une boutique reçoit une fiche dans le cahier de bons du
 * commerçant (uid « c<id> »). Il reste prospect jusqu'à son premier achat :
 * une commande livrée ou un bon enregistré à son nom.
 */
class CommerceService
{
    public const STATUSES = ['envoyee', 'acceptee', 'prete', 'livree', 'refusee', 'annulee'];

    /** Le commerçant fait avancer la commande dans cet ordre. */
    private const NEXT = [
        'envoyee' => ['acceptee', 'refusee', 'livree'],
        'acceptee' => ['prete', 'livree', 'refusee'],
        'prete' => ['livree', 'refusee'],
    ];

    public static function personUid(Account $customer): string
    {
        return 'c'.$customer->id;
    }

    // ------------------------------------------------------------ boutiques

    /** Boutiques trouvées par leur nom (commerçants actifs qui ont un nom de commerce). */
    public function search(Account $customer, string $q): array
    {
        $q = trim($q);
        $followed = ShopLink::query()->where('customer_id', $customer->id)->pluck('shop_id')->all();

        $query = Account::query()->where('role', 'merchant')->where('blocked', false)
            ->whereNotNull('name')->where('name', '!=', '');
        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where('name', 'ilike', $like);
        }

        return $query->orderBy('name')->limit(30)->get()
            ->map(fn (Account $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'hint' => '··· '.substr($s->phone, -3), // distingue deux boutiques du même nom
                'articles' => $this->catalogQuery($s)->count(),
                'followed' => in_array($s->id, $followed, true),
            ])->values()->all();
    }

    /** Liste des boutiques proposées à la création d'un compte client (pas encore connecté). */
    public function directory(): array
    {
        return Account::query()->where('role', 'merchant')->where('blocked', false)
            ->whereNotNull('name')->where('name', '!=', '')
            ->orderBy('name')->limit(300)->get()
            ->map(fn (Account $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'hint' => '··· '.substr($s->phone, -3),
                'articles' => $this->catalogQuery($s)->count(),
            ])->values()->all();
    }

    public function follow(Account $customer, int $shopId): ShopLink
    {
        $shop = Account::query()->where('id', $shopId)->where('role', 'merchant')->where('blocked', false)->first();
        if (! $shop) {
            throw new RuntimeException('Boutique introuvable.');
        }

        return DB::transaction(function () use ($customer, $shop) {
            $link = ShopLink::query()->firstOrCreate(
                ['customer_id' => $customer->id, 'shop_id' => $shop->id],
                ['person_uid' => self::personUid($customer)],
            );
            $this->ensurePerson($shop, $customer, $link->person_uid);

            return $link;
        });
    }

    public function unfollow(Account $customer, int $shopId): void
    {
        // Les bons et l'historique restent chez le commerçant.
        ShopLink::query()->where('customer_id', $customer->id)->where('shop_id', $shopId)->delete();
    }

    /** Fiche du client dans le cahier de bons du commerçant (synchronisée vers son téléphone). */
    private function ensurePerson(Account $shop, Account $customer, string $uid): void
    {
        $name = Str::limit(trim((string) ($customer->name ?: $customer->phone)), 40, '');
        $locked = Account::query()->lockForUpdate()->findOrFail($shop->id);
        $person = AccountPerson::query()->firstOrNew(['account_id' => $locked->id, 'uid' => $uid]);
        if ($person->exists && ! $person->deleted) {
            return;
        }
        $person->fill([
            'name' => $name !== '' ? $name : 'Client',
            'position' => 100000 + $customer->id, // après les noms de la liste
            'deleted' => false,
            'client_updated_at' => now()->getTimestampMs(),
            'rev' => ++$locked->rev,
        ])->save();
        $locked->save();
    }

    private function catalogQuery(Account $shop)
    {
        // Articles réglés par le commerçant : un prix ou une photo.
        return AccountFood::query()->where('account_id', $shop->id)->where('deleted', false)
            ->where(fn ($q) => $q->where('price', '>', 0)->orWhereNotNull('image_hash'));
    }

    // ------------------------------------------------------------ commandes

    /**
     * Commande passée par le client (éventuellement hors ligne, envoyée plus tard).
     * Les prix sont ceux de la boutique au moment où la commande arrive.
     *
     * @param  array<array{uid:string, q:int}>  $lines
     */
    public function placeOrder(Account $customer, string $uid, int $shopId, array $lines, ?string $note, int $orderedAt): ShopOrder
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,40}$/', $uid)) {
            throw new RuntimeException('Commande invalide.');
        }
        $existing = ShopOrder::query()->where('customer_id', $customer->id)->where('uid', $uid)->first();
        if ($existing) {
            return $existing; // déjà reçue (renvoi après une coupure réseau)
        }

        $link = $this->follow($customer, $shopId);
        $foods = $this->catalogQuery($link->shop)->get()->keyBy('uid');

        $items = [];
        $total = 0;
        foreach (array_slice($lines, 0, AccountService::MAX_FOODS) as $line) {
            $food = $foods[(string) ($line['uid'] ?? '')] ?? null;
            $q = max(0, min(999, (int) ($line['q'] ?? 0)));
            if (! $food || $q === 0) {
                continue;
            }
            $items[] = ['uid' => $food->uid, 'n' => $food->name, 'p' => $food->price, 'q' => $q];
            $total += $food->price * $q;
        }
        if (! $items) {
            throw new RuntimeException('La commande est vide.');
        }

        return ShopOrder::query()->create([
            'uid' => $uid,
            'customer_id' => $customer->id,
            'shop_id' => $link->shop_id,
            'items' => $items,
            'total' => $total,
            'note' => $note !== null && trim($note) !== '' ? Str::limit(trim($note), 300, '') : null,
            'status' => 'envoyee',
            'ordered_at' => $orderedAt > 0 ? $orderedAt : now()->getTimestampMs(),
            'status_at' => now(),
        ]);
    }

    public function cancelOrder(Account $customer, string $uid): ShopOrder
    {
        $order = ShopOrder::query()->where('customer_id', $customer->id)->where('uid', $uid)->firstOrFail();
        if ($order->status !== 'envoyee') {
            throw new RuntimeException('La boutique a déjà pris la commande en charge : contactez-la.');
        }
        $order->update(['status' => 'annulee', 'status_at' => now()]);

        return $order;
    }

    /** Le commerçant accepte, prépare, livre ou refuse. Livrée : le client devient client. */
    public function setStatus(Account $shop, int $orderId, string $status, ?string $mode): ShopOrder
    {
        $order = ShopOrder::query()->where('shop_id', $shop->id)->where('id', $orderId)->firstOrFail();
        if ($order->status === $status) {
            return $order; // renvoi d'une action déjà appliquée
        }
        if (! in_array($status, self::NEXT[$order->status] ?? [], true)) {
            throw new RuntimeException('Cette commande ne peut plus changer d\'état.');
        }

        DB::transaction(function () use ($order, $status, $mode) {
            $order->update([
                'status' => $status,
                'mode' => $status === 'livree' ? ($mode === 'bon' ? 'bon' : 'cash') : null,
                'status_at' => now(),
            ]);
            if ($status === 'livree') {
                ShopLink::query()->where('shop_id', $order->shop_id)->where('customer_id', $order->customer_id)
                    ->whereNull('first_purchase_at')->update(['first_purchase_at' => now()]);
            }
        });

        return $order->refresh();
    }

    // ------------------------------------------------------------ messages

    /**
     * Message ou promotion envoyé par le commerçant. Seuls ses clients (pas les
     * prospects) peuvent le recevoir, et seulement ceux qu'il a cochés.
     *
     * @param  int[]  $recipients  ids des comptes clients
     */
    public function sendMessage(Account $shop, string $uid, string $kind, string $title, string $body, array $recipients): ShopMessage
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,40}$/', $uid)) {
            throw new RuntimeException('Message invalide.');
        }
        $existing = ShopMessage::query()->where('shop_id', $shop->id)->where('uid', $uid)->first();
        if ($existing) {
            return $existing;
        }
        $title = Str::limit(trim($title), 80, '');
        $body = Str::limit(trim($body), 2000, '');
        if ($title === '' && $body === '') {
            throw new RuntimeException('Le message est vide.');
        }

        $clients = array_keys(array_filter($this->customerStatuses($shop), fn ($s) => $s === 'client'));
        $to = array_values(array_intersect($clients, array_map('intval', $recipients)));
        if (! $to) {
            throw new RuntimeException('Choisissez au moins un client.');
        }

        return DB::transaction(function () use ($shop, $uid, $kind, $title, $body, $to) {
            $message = ShopMessage::query()->create([
                'uid' => $uid,
                'shop_id' => $shop->id,
                'kind' => $kind === 'promo' ? 'promo' : 'info',
                'title' => $title !== '' ? $title : Str::limit($body, 60),
                'body' => $body,
            ]);
            DB::table('shop_message_recipients')->insert(
                array_map(fn ($id) => ['message_id' => $message->id, 'customer_id' => $id], $to)
            );

            return $message;
        });
    }

    public function markRead(Account $customer, array $messageIds): void
    {
        DB::table('shop_message_recipients')->where('customer_id', $customer->id)
            ->whereIn('message_id', array_map('intval', array_slice($messageIds, 0, 500)))
            ->whereNull('read_at')->update(['read_at' => now()]);
    }

    // ------------------------------------------------------------ état complet

    /**
     * prospect ou client pour chaque client qui suit la boutique.
     *
     * @return array<int, string> id du compte client => statut
     */
    public function customerStatuses(Account $shop): array
    {
        $links = ShopLink::query()->where('shop_id', $shop->id)->get();
        $buyers = AccountEntry::query()->where('account_id', $shop->id)->where('deleted', false)->where('pay', false)
            ->whereIn('person_uid', $links->pluck('person_uid'))->distinct()->pluck('person_uid')->flip();

        $out = [];
        foreach ($links as $l) {
            $out[$l->customer_id] = $l->first_purchase_at || isset($buyers[$l->person_uid]) ? 'client' : 'prospect';
        }

        return $out;
    }

    /** Tout ce que le téléphone garde pour l'espace boutique / client (lu aussi hors ligne). */
    public function snapshot(Account $account): array
    {
        return $account->role === 'customer' ? $this->customerSnapshot($account) : $this->shopSnapshot($account);
    }

    private function shopSnapshot(Account $shop): array
    {
        $statuses = $this->customerStatuses($shop);
        $links = ShopLink::query()->where('shop_id', $shop->id)->with('customer')->get();

        return [
            'role' => 'merchant',
            'customers' => $links->map(fn (ShopLink $l) => [
                'id' => $l->customer_id,
                'name' => $l->customer?->name ?: $l->customer?->phone,
                'phone' => $l->customer?->phone,
                'person' => $l->person_uid,
                'status' => $statuses[$l->customer_id] ?? 'prospect',
                'since' => $l->created_at?->getTimestampMs(),
                'firstPurchaseAt' => $l->first_purchase_at?->getTimestampMs(),
            ])->values(),
            'orders' => ShopOrder::query()->where('shop_id', $shop->id)->with('customer')
                ->orderByDesc('ordered_at')->limit(300)->get()
                ->map(fn (ShopOrder $o) => $this->order($o) + [
                    'customer' => ['id' => $o->customer_id, 'name' => $o->customer?->name ?: $o->customer?->phone, 'phone' => $o->customer?->phone, 'person' => 'c'.$o->customer_id],
                ])->values(),
            'messages' => ShopMessage::query()->where('shop_id', $shop->id)->orderByDesc('id')->limit(100)->get()
                ->map(function (ShopMessage $m) {
                    $r = DB::table('shop_message_recipients')->where('message_id', $m->id);

                    return [
                        'id' => $m->id, 'uid' => $m->uid, 'kind' => $m->kind, 'title' => $m->title, 'body' => $m->body,
                        'at' => $m->created_at?->getTimestampMs(),
                        'sent' => (clone $r)->count(), 'read' => (clone $r)->whereNotNull('read_at')->count(),
                    ];
                })->values(),
            'serverTime' => now()->getTimestampMs(),
        ];
    }

    private function customerSnapshot(Account $customer): array
    {
        $links = ShopLink::query()->where('customer_id', $customer->id)->with('shop')->get();

        $shops = $links->filter(fn (ShopLink $l) => $l->shop)->map(function (ShopLink $l) {
            $shop = $l->shop;
            $entries = AccountEntry::query()->where('account_id', $shop->id)->where('person_uid', $l->person_uid)
                ->where('deleted', false)->orderBy('happened_at')->get()
                ->map(fn (AccountEntry $e) => [
                    'uid' => $e->uid, 'person' => $e->person_uid, 'amount' => $e->amount, 'pay' => $e->pay,
                    'label' => $e->label, 'ref' => $e->ref, 'items' => $e->items ?? [], 'at' => $e->happened_at,
                ])->values();

            return [
                'id' => $shop->id,
                'name' => $shop->name ?: $shop->phone,
                'phone' => $shop->phone,
                'open' => ! $shop->blocked,
                'status' => $l->first_purchase_at || $entries->contains(fn ($e) => ! $e['pay']) ? 'client' : 'prospect',
                'since' => $l->created_at?->getTimestampMs(),
                'catalog' => $this->catalogQuery($shop)->orderBy('position')->get()
                    ->map(fn (AccountFood $f) => ['uid' => $f->uid, 'name' => $f->name, 'price' => $f->price, 'img' => $f->image_hash])->values(),
                'entries' => $entries,
            ];
        })->values();

        $messages = DB::table('shop_message_recipients as r')
            ->join('shop_messages as m', 'm.id', '=', 'r.message_id')
            ->join('accounts as a', 'a.id', '=', 'm.shop_id')
            ->where('r.customer_id', $customer->id)
            ->orderByDesc('m.id')->limit(200)
            ->get(['m.id', 'm.kind', 'm.title', 'm.body', 'm.created_at', 'r.read_at', 'a.id as shop_id', 'a.name as shop_name'])
            ->map(fn ($m) => [
                'id' => $m->id, 'kind' => $m->kind, 'title' => $m->title, 'body' => $m->body,
                'at' => strtotime((string) $m->created_at) * 1000, 'read' => $m->read_at !== null,
                'shop' => ['id' => $m->shop_id, 'name' => $m->shop_name],
            ])->values();

        return [
            'role' => 'customer',
            'shops' => $shops,
            'orders' => ShopOrder::query()->where('customer_id', $customer->id)->with('shop')
                ->orderByDesc('ordered_at')->limit(200)->get()
                ->map(fn (ShopOrder $o) => $this->order($o) + ['shop' => ['id' => $o->shop_id, 'name' => $o->shop?->name ?: $o->shop?->phone]])
                ->values(),
            'messages' => $messages,
            'serverTime' => now()->getTimestampMs(),
        ];
    }

    private function order(ShopOrder $o): array
    {
        return [
            'id' => $o->id, 'uid' => $o->uid, 'items' => $o->items, 'total' => $o->total, 'note' => $o->note,
            'status' => $o->status, 'mode' => $o->mode, 'at' => $o->ordered_at, 'statusAt' => $o->status_at?->getTimestampMs(),
        ];
    }
}
