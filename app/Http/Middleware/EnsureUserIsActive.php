<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out (web) or rejects (API) a logged-in user whose account or employee
 * record was deactivated after they logged in.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->fresh(['employee'])->canSignIn()) {
            if ($request->is('api/*')) {
                $user->currentAccessToken()?->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'This account is deactivated.',
                    'errors' => null,
                ], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account is deactivated. Contact the administrator.']);
        }

        return $next($request);
    }
}
