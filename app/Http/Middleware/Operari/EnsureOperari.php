<?php

namespace App\Http\Middleware\Operari;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperari
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, [UserRole::Operari, UserRole::Admin, UserRole::Qc], true)) {
            abort(403);
        }

        return $next($request);
    }
}
