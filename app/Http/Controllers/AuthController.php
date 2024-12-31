<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users|max:50',
            'pin' => 'required|string|min:6|max:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'pin' => $request->pin
        ]);

        $categories = ['Education', 'Social', 'Entertainment'];
        foreach ($categories as $categoryName) {
            Category::create([
                'user_id' => $user->id,
                'category_name' => $categoryName,
                'is_default' => true  // Set sebagai category default
            ]);
        }

        

        // Login setelah register
        $token = Auth::login($user);

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'pin' => $user->pin, // Kosongkan pin
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'token' => $token,
            'token_expires_in' => config('jwt.ttl') * 60,
            'token_type' => 'bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'pin' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();
        
        // Cek apakah user ada dan pin sesuai
        if (!$user || $request->pin !== $user->pin) {
            return response()->json([
                'status' => false,
                'message' => 'Username or PIN is incorrect'
            ], 401);
        }
        

        // Generate token
        $token = Auth::login($user);

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'pin' => $user->pin, // Kosongkan pin
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'token' => $token,
            'token_expires_in' => config('jwt.ttl') * 60,
            'token_type' => 'bearer'
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function refresh()
    {
        try {
            $token = Auth::refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token cannot be refreshed, please login again'
            ], 401);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
            'user' => Auth::user()
        ]);
    }
}