<?php

namespace App\Http\Middleware;

use App\Services\AccountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Reconnaît le téléphone par son jeton (Authorization: Bearer …). */
class EnsureAccountToken
{
    public function __construct(private AccountService $accounts) {}

    public function handle(Request $request, Closure $next): Response
    {
        $account = $this->accounts->resolveToken($request->bearerToken());
        if (! $account) {
            return response()->json(['message' => 'Session expirée, reconnectez-vous.'], 401);
        }
        if ($account->blocked) {
            return response()->json([
                'message' => 'Ce compte est suspendu.',
                'blocked' => true,
                'account' => $this->accounts->payload($account),
            ], 403);
        }

        $request->attributes->set('account', $account);

        return $next($request);
    }
}
