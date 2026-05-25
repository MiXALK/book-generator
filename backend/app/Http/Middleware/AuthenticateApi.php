<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token missing.',
            ], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Invalid token.',
            ], 401);
        }

        if ($user->api_token_expires_at && now()->greaterThan($user->api_token_expires_at)) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Token expired.',
            ], 401);
        }

        // Authenticate the user for the current request
        Auth::setUser($user);

        return $next($request);
    }
}
