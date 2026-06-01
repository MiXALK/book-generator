<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\UserRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

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

        $user = $this->users->findByApiToken($token);

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

        Auth::setUser($user);

        return $next($request);
    }
}
