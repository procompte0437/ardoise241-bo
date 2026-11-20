<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountFood;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** API de l'application mobile (hors ligne d'abord, synchronisée en ligne). */
class AppController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'confirmed'],
            'name' => ['nullable', 'string', 'max:60'],
            'device' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'in:merchant,customer'],
        ], ['code.confirmed' => 'Les deux codes ne sont pas identiques.']);

        try {
            [$account, $token] = $this->accounts->register($data['phone'], $data['code'], $data['name'] ?? null, $data['device'] ?? null, $data['role'] ?? 'merchant');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['token' => $token, 'account' => $this->accounts->payload($account)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:10'],
            'device' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            [$account, $token] = $this->accounts->login($data['phone'], $data['code'], $data['device'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['token' => $token, 'account' => $this->accounts->payload($account)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['account' => $this->accounts->payload($this->account($request))]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'since' => ['nullable', 'integer', 'min:0'],
            'people' => ['nullable', 'array'],
            'foods' => ['nullable', 'array'],
            'entries' => ['nullable', 'array'],
            'device' => ['nullable', 'string', 'max:120'],
        ]);

        // validate() ne garde pas le contenu des tableaux sans règle : on relit les lignes brutes,
        // le service les nettoie une à une.
        return response()->json($this->accounts->sync(
            $this->account($request),
            [
                'people' => (array) $request->input('people', []),
                'foods' => (array) $request->input('foods', []),
                'entries' => (array) $request->input('entries', []),
            ],
            (int) ($data['since'] ?? 0),
            $data['device'] ?? null,
        ));
    }

    public function uploadImage(Request $request, string $uid): JsonResponse
    {
        $request->validate(['photo' => ['required', 'file', 'image', 'max:8192']]);

        try {
            $food = $this->accounts->storeImage($this->account($request), $uid, $request->file('photo'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['uid' => $food->uid, 'img' => $food->image_hash]);
    }

    public function image(Request $request, string $uid): Response
    {
        $food = AccountFood::query()->where('account_id', $this->account($request)->id)->where('uid', $uid)->first();
        abort_if(! $food || ! $food->image_data, 404);

        return response(base64_decode($food->image_data), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'ETag' => '"'.$food->image_hash.'"',
        ]);
    }

    public function changeCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current' => ['required', 'string'],
            'code' => ['required', 'string', 'confirmed'],
        ], ['code.confirmed' => 'Les deux codes ne sont pas identiques.']);

        $account = $this->account($request);
        if (! Hash::check($data['current'], $account->code_hash)) {
            return response()->json(['message' => 'Code actuel incorrect.'], 422);
        }

        try {
            $this->accounts->setCode($account, $data['code']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Code modifié.', 'account' => $this->accounts->payload($account)]);
    }

    /** Nom du commerce (ou du client), affiché aux clients et dans la recherche de boutiques. */
    public function profile(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $name = trim($data['name']);
        if ($name === '') {
            return response()->json(['message' => 'Entrez un nom.'], 422);
        }

        $account = $this->account($request);
        $account->name = $name;
        $account->save();

        return response()->json(['account' => $this->accounts->payload($account)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->accounts->forgetToken((string) $request->bearerToken());

        return response()->json(['message' => 'Déconnecté.']);
    }

    private function account(Request $request): Account
    {
        return $request->attributes->get('account');
    }
}
