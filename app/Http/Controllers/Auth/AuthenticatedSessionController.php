<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;


class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): Response
    {
        $validate = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where("email", $request->email)->first();
        if (!$user || !Hash::check($validate["password"], $user->password)) {
            return response()->json(["message" => "invalid credentials"], Response::HTTP_FORBIDDEN);
        }
        $token = $user->createToken('userToken')->plainTextToken;
        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Destroy an authenticated session.
     */

    public function destroy(Request $request)
    {
        $email = Auth::user()->email;
        $user = User::where('email', $email)->first();
        $user->tokens()->delete();
        return response([
            'message' => 'logged out'
        ], 200);
    }
}
