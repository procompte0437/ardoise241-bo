<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountEntry;
use App\Models\AccountFood;
use App\Models\AccountPerson;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

/** Back-office de suivi des comptes de l'application mobile. */
class ConsoleController extends Controller
{
    public const STATUSES = [
        'essai' => 'En essai',
        'abonne' => 'Abonné',
        'expire' => 'Expiré',
        'bloque' => 'Suspendu',
        'client' => 'Espace client',
    ];

    public function __construct(private AccountService $accounts) {}

    public function loginForm(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('gestion.home') : view('gestion.connexion');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->with('error', 'E-mail ou mot de passe incorrect.')->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('gestion.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gestion.login');
    }

    public function home(): View
    {
        $all = Account::query()->get();
        $counts = ['total' => $all->count()] + array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($all as $account) {
            $counts[$account->status()]++;
        }

        $soon = $all->filter(fn (Account $a) => ! $a->isCustomer() && ! $a->blocked && ! $a->isExpired() && $a->daysLeft() <= 7)
            ->sortBy(fn (Account $a) => $a->accessEndsAt()->getTimestamp())->take(8);

        return view('gestion.tableau', [
            'counts' => $counts,
            'newWeek' => $all->filter(fn (Account $a) => $a->created_at->gt(now()->subDays(7)))->count(),
            'syncedDay' => $all->filter(fn (Account $a) => $a->last_sync_at?->gt(now()->subDay()))->count(),
            'soon' => $soon,
            'recent' => $all->sortByDesc('created_at')->take(8),
            'entries' => AccountEntry::query()->where('deleted', false)->count(),
            'globalDays' => AccountService::globalTrialDays(),
        ]);
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('statut', '');

        $query = Account::query()->orderByDesc('created_at');
        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q);
            $query->where(function ($w) use ($q, $digits) {
                $w->where('name', 'ilike', '%'.$q.'%');
                if ($digits !== '') {
                    $w->orWhere('phone', 'like', '%'.$digits.'%');
                }
            });
        }

        // Le statut dépend de la durée globale : on le calcule en PHP.
        $rows = $query->get();
        if (isset(self::STATUSES[$status])) {
            $rows = $rows->filter(fn (Account $a) => $a->status() === $status)->values();
        }

        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator($rows->forPage($page, 25)->values(), $rows->count(), 25, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('gestion.comptes', ['accounts' => $paginator, 'q' => $q, 'status' => $status]);
    }

    public function show(Account $account): View
    {
        $debts = $this->accounts->debts($account);
        $people = AccountPerson::query()->where('account_id', $account->id)->where('deleted', false)->pluck('name', 'uid');
        $owing = collect($debts)->filter(fn ($d) => $d > 0)->sortDesc()
            ->map(fn ($debt, $uid) => ['name' => $people[$uid] ?? self::defaultName($uid), 'debt' => $debt]);

        return view('gestion.compte', [
            'account' => $account,
            'owing' => $owing,
            'totalDebt' => $owing->sum('debt'),
            'renamed' => $people->count(),
            'foods' => AccountFood::query()->where('account_id', $account->id)->where('deleted', false)
                ->orderBy('position')->get(['uid', 'name', 'price', 'image_hash', 'position']),
            'entries' => AccountEntry::query()->where('account_id', $account->id)->where('deleted', false)
                ->orderByDesc('happened_at')->limit(15)->get(),
            'entryCount' => AccountEntry::query()->where('account_id', $account->id)->where('deleted', false)->count(),
            'people' => $people,
            'tokens' => $account->tokens()->orderByDesc('last_used_at')->get(),
            'globalDays' => AccountService::globalTrialDays(),
            'invToday' => $this->accounts->inventory($account, now()->startOfDay()->getTimestampMs()),
            'invMonth' => $this->accounts->inventory($account, now()->subDays(29)->startOfDay()->getTimestampMs()),
        ]);
    }

    public function image(Account $account, string $uid)
    {
        $food = AccountFood::query()->where('account_id', $account->id)->where('uid', $uid)->first();
        abort_if(! $food || ! $food->image_data, 404);

        return response(base64_decode($food->image_data), 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=86400']);
    }

    public function trial(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate(['trial_days' => ['nullable', 'integer', 'min:0', 'max:3650']]);
        $account->trial_days = $data['trial_days'] ?? null;
        $account->save();

        return back()->with('ok', $account->trial_days === null
            ? 'Ce compte suit maintenant la durée globale.'
            : 'Durée d\'essai de ce compte : '.$account->trial_days.' jours.');
    }

    public function renew(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:3650']]);
        $from = $account->isExpired() ? now() : $account->accessEndsAt();
        $account->paid_until = $from->copy()->addDays((int) $data['days']);
        $account->save();

        return back()->with('ok', 'Abonnement prolongé jusqu\'au '.$account->paid_until->translatedFormat('d F Y').'.');
    }

    public function stop(Account $account): RedirectResponse
    {
        $account->paid_until = null;
        $account->trial_days = 0;
        $account->save();

        return back()->with('ok', 'Accès terminé : l\'application demandera de renouveler l\'abonnement.');
    }

    public function block(Account $account): RedirectResponse
    {
        $account->blocked = ! $account->blocked;
        $account->save();

        return back()->with('ok', $account->blocked ? 'Compte suspendu.' : 'Compte réactivé.');
    }

    public function resetCode(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string']]);
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        try {
            $this->accounts->setCode($account, $code);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Le code n'est affiché qu'une fois, pour le communiquer au client.
        return back()->with('newCode', $code);
    }

    public function devices(Account $account): RedirectResponse
    {
        $account->tokens()->delete();

        return back()->with('ok', 'Tous les appareils sont déconnectés. Le client devra se reconnecter avec son numéro et son code.');
    }

    public function note(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000'], 'name' => ['nullable', 'string', 'max:60']]);
        $account->note = $data['note'] ?? null;
        $account->name = $data['name'] ?? $account->name;
        $account->save();

        return back()->with('ok', 'Fiche enregistrée.');
    }

    public function settings(): View
    {
        return view('gestion.reglages', ['globalDays' => AccountService::globalTrialDays()]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['trial_days' => ['required', 'integer', 'min:0', 'max:3650']]);
        AccountService::setGlobalTrialDays((int) $data['trial_days']);

        return back()->with('ok', 'Durée d\'essai globale : '.$data['trial_days'].' jours. Elle s\'applique à tous les comptes sans durée particulière.');
    }

    public function saveSupport(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'confirmed'],
        ], ['code.confirmed' => 'Les deux codes ne sont pas identiques.']);

        try {
            AccountService::setSupportCode($data['code']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Code admin Ardoise241 modifié. Les téléphones le reçoivent à leur prochaine synchronisation.');
    }

    public static function defaultName(string $uid): string
    {
        if ($uid === AccountService::CASH) {
            return 'Vente encaissée';
        }
        return preg_match('/^p(\d+)$/', $uid, $m) ? 'Nom '.$m[1] : $uid;
    }
}
