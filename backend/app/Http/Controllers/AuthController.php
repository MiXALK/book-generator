<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Get the Google Auth URL.
     */
    public function getGoogleUrl()
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        $redirectUri = env('GOOGLE_REDIRECT_URI');

        if (! $clientId || ! $redirectUri) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'Google Client ID or Redirect URI is not configured on the server.',
            ], 500);
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        $url = "https://accounts.google.com/o/oauth2/v2/auth?{$query}";

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle Google Callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        $redirectUri = env('GOOGLE_REDIRECT_URI');

        if (! $clientId || ! $clientSecret || ! $redirectUri) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'Google Client ID, Secret, or Redirect URI is not configured on the server.',
            ], 500);
        }

        // Exchange Authorization Code for Tokens
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
        ]);

        if (! $response->successful()) {
            return response()->json([
                'error' => 'OAuth token exchange failed',
                'message' => $response->json('error_description') ?? 'Failed to exchange OAuth code.',
            ], 400);
        }

        $idToken = $response->json('id_token');

        if (! $idToken) {
            return response()->json([
                'error' => 'OAuth token exchange failed',
                'message' => 'No ID token received from Google.',
            ], 400);
        }

        // Verify ID Token with Google Info Endpoint
        $verifyResponse = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $verifyResponse->successful()) {
            return response()->json([
                'error' => 'OAuth validation failed',
                'message' => 'Failed to verify Google ID token.',
            ], 400);
        }

        $googleProfile = $verifyResponse->json();

        $googleId = $googleProfile['sub'] ?? null;
        $email = $googleProfile['email'] ?? null;
        $name = $googleProfile['name'] ?? ($googleProfile['given_name'] ?? 'User');
        $avatarUrl = $googleProfile['picture'] ?? null;

        if (! $googleId || ! $email) {
            return response()->json([
                'error' => 'OAuth validation failed',
                'message' => 'Incomplete profile returned from Google.',
            ], 400);
        }

        // Create or update the user
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // Update existing user with Google details
            $user->update([
                'google_id' => $googleId,
                'name' => $name,
                'avatar_url' => $avatarUrl ?? $user->avatar_url,
            ]);
        } else {
            // Create a new user (with dummy password since password column is nullable or hashed anyway)
            $user = User::create([
                'google_id' => $googleId,
                'email' => $email,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'plan' => 'free',
                'subscription_status' => 'inactive',
                'password' => bcrypt(Str::random(24)),
            ]);
        }

        // Generate a secure API Token
        $apiToken = Str::random(80);
        $user->update([
            'api_token' => $apiToken,
            'api_token_expires_at' => now()->addDays(30), // Expire after 30 days
        ]);

        return response()->json([
            'token' => $apiToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'plan' => $user->plan,
                'subscription_status' => $user->subscription_status,
                'expires_at' => $user->api_token_expires_at,
            ],
        ]);
    }

    /**
     * Log out current user and clear token.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->update([
                'api_token' => null,
                'api_token_expires_at' => null,
            ]);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get details of the currently authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'plan' => $user->plan,
                'subscription_status' => $user->subscription_status,
            ],
        ]);
    }
}
