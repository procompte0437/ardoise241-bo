<?php

namespace App\Http\Middleware;

use App\Services\LessyStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLessyAdmin
{
    public function __construct(private LessyStore $store) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Admin-Token');
        if (! is_string($token) || ! $this->store->adminTokenValid($token)) {
            return response()->json(['message' => 'Code admin requis.'], 403);
        }

        return $next($request);
    }
}
