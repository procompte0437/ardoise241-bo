<?php

namespace App\Http\Middleware;

use App\Services\LessyStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLessyUnlocked
{
    public function __construct(private LessyStore $store) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->store->ensureInstalled();

        if (! $this->store->isLocked()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'locked' => true,
                'message' => "La période d'essai est terminée.",
            ], 423);
        }

        return redirect()->route('verrou');
    }
}
