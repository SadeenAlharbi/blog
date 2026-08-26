<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Registered successfully.',
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        // A disabled account gets NO new token, and any tokens it still holds
        // are revoked so it cannot keep using the API.
        if (! $user->is_active) {
            $user->tokens()->delete();

            return response()->json(['message' => EnsureAccountIsActive::MESSAGE], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Logged in successfully.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            // Bearer-token client: revoke exactly the token in use, so the
            // caller's other devices stay signed in.
            $token->delete();
        } elseif ($user) {
            /*
             * The caller is authenticated by Sanctum's cookie/session guard
             * (a same-domain SPA) rather than by a bearer token, so there is no
             * token row to delete — end the session instead. Without this
             * branch the endpoint fataled on a null token.
             */
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json([
            'data' => null,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user()),
            'message' => 'OK',
        ]);
    }
}
