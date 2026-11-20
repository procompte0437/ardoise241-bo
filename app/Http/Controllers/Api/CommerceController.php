<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountFood;
use App\Services\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/** Espace client (boutiques, commandes, messages) et suivi côté commerçant. */
class CommerceController extends Controller
{
    public function __construct(private CommerceService $commerce) {}

    /** Tout l'état boutique/client, gardé sur le téléphone pour le hors ligne. */
    public function state(Request $request): JsonResponse
    {
        return response()->json($this->commerce->snapshot($this->account($request)));
    }

    /** Boutiques à cocher pendant la création d'un compte client. */
    public function directory(): JsonResponse
    {
        return response()->json(['shops' => $this->commerce->directory()]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:60']]);

        return response()->json(['shops' => $this->commerce->search($this->customer($request), (string) ($data['q'] ?? ''))]);
    }

    public function follow(Request $request, int $shop): JsonResponse
    {
        return $this->run(fn () => $this->commerce->follow($this->customer($request), $shop) && true);
    }

    public function unfollow(Request $request, int $shop): JsonResponse
    {
        return $this->run(fn () => $this->commerce->unfollow($this->customer($request), $shop) || true);
    }

    public function order(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uid' => ['required', 'string', 'max:40'],
            'shop' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:300'],
            'at' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        return $this->run(fn () => $this->commerce->placeOrder(
            $this->customer($request), $data['uid'], (int) $data['shop'],
            (array) $request->input('items', []), $data['note'] ?? null, (int) ($data['at'] ?? 0),
        ) && true);
    }

    public function cancel(Request $request, string $uid): JsonResponse
    {
        return $this->run(fn () => $this->commerce->cancelOrder($this->customer($request), $uid) && true);
    }

    public function read(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array']]);

        return $this->run(fn () => $this->commerce->markRead($this->customer($request), (array) $request->input('ids')) || true);
    }

    public function status(Request $request, int $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:acceptee,prete,livree,refusee'],
            'mode' => ['nullable', 'in:cash,bon'],
        ]);

        return $this->run(fn () => $this->commerce->setStatus($this->merchant($request), $order, $data['status'], $data['mode'] ?? null) && true);
    }

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uid' => ['required', 'string', 'max:40'],
            'kind' => ['nullable', 'in:info,promo'],
            'title' => ['nullable', 'string', 'max:80'],
            'body' => ['nullable', 'string', 'max:2000'],
            'to' => ['required', 'array', 'min:1'],
        ]);

        return $this->run(fn () => $this->commerce->sendMessage(
            $this->merchant($request), $data['uid'], $data['kind'] ?? 'info',
            (string) ($data['title'] ?? ''), (string) ($data['body'] ?? ''), (array) $request->input('to'),
        ) && true);
    }

    /** Photo d'un article d'une boutique (publique : c'est la vitrine). */
    public function image(int $shop, string $uid): Response
    {
        $food = AccountFood::query()->where('account_id', $shop)->where('uid', $uid)->where('deleted', false)->first();
        abort_if(! $food || ! $food->image_data, 404);

        return response(base64_decode($food->image_data), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => '"'.$food->image_hash.'"',
        ]);
    }

    private function run(callable $fn): JsonResponse
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    private function account(Request $request): Account
    {
        return $request->attributes->get('account');
    }

    private function customer(Request $request): Account
    {
        $a = $this->account($request);
        abort_unless($a->role === 'customer', 403, 'Réservé aux comptes clients.');

        return $a;
    }

    private function merchant(Request $request): Account
    {
        $a = $this->account($request);
        abort_if($a->role === 'customer', 403, 'Réservé aux commerçants.');

        return $a;
    }
}
