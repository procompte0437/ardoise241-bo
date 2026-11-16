<?php

namespace App\Http\Controllers\Bo;

use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Models\Person;
use App\Services\LessyStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class DeskController extends Controller
{
    public function __construct(private LessyStore $store) {}

    public function home(): RedirectResponse
    {
        return redirect()->route('noms');
    }

    public function connexionForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('noms');
        }

        return view('bo.connexion');
    }

    public function connexion(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            return back()->with('error', 'Identifiants incorrects.')->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('lessy_admin', true);

        return redirect()->route('noms');
    }

    public function deconnexion(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion');
    }

    public function verrouForm(): View|RedirectResponse
    {
        $this->store->ensureInstalled();
        if (! $this->store->isLocked()) {
            return redirect()->route('noms');
        }

        return view('bo.verrou');
    }

    public function verrou(Request $request): RedirectResponse
    {
        $this->store->ensureInstalled();
        if (! $this->store->unlock((string) $request->input('code', ''))) {
            return back()->with('error', 'Code incorrect.');
        }

        return redirect()->route('noms');
    }

    public function noms(): View
    {
        $people = Person::query()->orderBy('position')->orderBy('id')->get();

        return view('bo.noms', [
            'people' => $people,
            'admin' => $this->admin(),
            'room' => LessyStore::MAX_NAMES - $people->count(),
            'maxNames' => LessyStore::MAX_NAMES,
        ]);
    }

    public function enter(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->store->checkAdmin((string) $request->input('code', ''))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Code incorrect.'], 422);
            }

            return back()->with('error', 'Code incorrect.');
        }

        $request->session()->put('lessy_admin', true);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('toast', 'Mode admin activé.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $request->session()->forget('lessy_admin');

        return redirect()->route('noms');
    }

    public function addNames(Request $request): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $text = (string) $request->input('text', '');
        $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $text) ?: [])));

        try {
            $added = $this->store->addNames($text);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $added.' nom(s) ajouté(s).';
        if ($added < count($lines)) {
            $message .= ' Les doublons ou le surplus ont été ignorés.';
        }

        return back()->with('toast', $message);
    }

    public function rename(Request $request, Person $person): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        try {
            $this->store->rename($person, (string) $request->input('name', ''));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function bilan(Person $person): View
    {
        $person->load('entries.items');

        return view('bo.bilan', [
            'person' => $person,
            'admin' => $this->admin(),
            'when' => fn ($date) => $this->store->whenLabel($date),
        ]);
    }

    public function erase(Request $request, Person $person): RedirectResponse
    {
        try {
            $this->store->erase($person, (string) $request->input('code', ''));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('toast', 'Bilan effacé.');
    }

    public function pay(Request $request, Person $person): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        try {
            $this->store->pay($person, (int) round((float) $request->input('amount', 0)));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function session(Person $person): View
    {
        $foods = Food::query()->orderBy('position')->orderBy('id')->get();

        return view('bo.session', [
            'person' => $person,
            'foods' => $foods,
            'admin' => $this->admin(),
        ]);
    }

    public function validateSession(Request $request, Person $person): JsonResponse
    {
        $counts = $request->input('counts', []);
        if (! is_array($counts)) {
            $counts = [];
        }

        try {
            $result = $this->store->addSession($person, $counts);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'amount' => $result['amount'],
            'redirect' => route('bilan', $person),
        ]);
    }

    public function aliments(): View|RedirectResponse
    {
        if (! $this->admin()) {
            return redirect()->route('noms');
        }

        $foods = Food::query()->orderBy('position')->orderBy('id')->get();

        return view('bo.aliments', [
            'foods' => $foods,
            'maxFoods' => LessyStore::MAX_FOODS,
        ]);
    }

    public function addFood(): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        try {
            $this->store->addFood();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function addPhotos(Request $request): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $files = $request->file('photos', []);
        if (! is_array($files) || $files === []) {
            return back()->with('error', 'Image illisible.');
        }

        try {
            $added = $this->store->addPhotos($files);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('toast', $added.' image(s) ajoutée(s).');
    }

    public function updateFood(Request $request, Food $food): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $this->store->updateFood(
            $food,
            $request->exists('name') ? (string) $request->input('name') : null,
            $request->exists('price') ? $request->input('price') : null,
        );

        if ($request->hasFile('photo')) {
            try {
                $this->store->storeImage($food, $request->file('photo'));
            } catch (RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        if ($request->input('back') === 'session' && $request->filled('person')) {
            return redirect()->route('session', $request->input('person'));
        }

        return back();
    }

    public function deleteFood(Food $food): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $this->store->deleteFood($food);

        return back();
    }

    public function changeCode(Request $request): RedirectResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        try {
            $this->store->changeCode((string) $request->input('code', ''));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('toast', 'Code modifié.');
    }

    private function admin(): bool
    {
        return session('lessy_admin') === true;
    }

    private function denyUnlessAdmin(): ?RedirectResponse
    {
        if ($this->admin()) {
            return null;
        }

        return back()->with('error', 'Code admin requis.');
    }
}
