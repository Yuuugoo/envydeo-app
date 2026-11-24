<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Social features need a username (for profile URLs and @mentions): send new users to pick one first. */
class EnsureUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->username) {
            return redirect()->route('welcome', ['next' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
