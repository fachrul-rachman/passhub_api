<?php

namespace App\Http\Controllers;

use App\Models\Password;
use App\Models\Category;

use App\Services\PasswordGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PasswordController extends Controller
{
    protected $passwordGenerator;

    public function __construct(PasswordGeneratorService $opasswordGenerator)
    {
        $this->middleware('auth:api');
        $this->passwordGenerator = $opasswordGenerator;
    }

    public function index()
    {
        $passwords = Password::where('user_id', Auth::id())
        ->with(['category' => function($query) {
            $query->select('id', 'category_name', 'is_default');
        }])
        ->get();

    return response()->json($passwords);
    }

    public function store(Request $request)
    {
        Log::info('Incoming Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|max:100',
            'email' => 'required|string|max:100',
            'password' => ['required_if:generate,false', 'nullable', 'string', 'max:255'],
            'generate' => 'required|boolean',
            'length' => ['required_if:generate,true', 'nullable', 'integer', 'min:8', 'max:100'],
            'uppercase_count' => ['required_if:generate,true', 'nullable', 'integer', 'min:0'],
            'number_count' => ['required_if:generate,true', 'nullable', 'integer', 'min:0'],
            'symbol_count' => ['required_if:generate,true', 'nullable', 'integer', 'min:0'],
            'category_id' => 'nullable|exists:categories,id',
            'img_platform' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ],422);
        }

        if ($request->category_id) {
            $categoryBelongsToUser = Auth::user()
            ->categories()
            ->where('id', $request->category_id)
            ->exists();

            if (!$categoryBelongsToUser) {
                return response()->json([
                    'message' => 'Category not found'
                ],404);
            }
        }
        

        $password = $request->password;

        if ($request->generate) {
            $password = $this->passwordGenerator->generate(
                $request->length,
                $request->uppercase_count,
                $request->number_count,
                $request->symbol_count
            );
        }

        $newPassword = Password::create([
            'user_id' => Auth::id(),
            'platform' => $request->platform,
            'img_platform' => $request->img_platform,
            'email' => $request->email,
            'password' => $password,
            'category_id' => $request->category_id
        ]);

        return response()->json($newPassword, 201);
    }

    public function show($id)
    {
        $password = Password::where('user_id', Auth::id())
        ->where('id', $id)
        ->with(['category' => function($query) {
            $query->select('id', 'category_name', 'is_default');
        }])
        ->first();

        if (!$password) {
            return response()->json([
                'message' => 'Password not found'
            ], 404);
        }

        return response()->json($password);
    }

    public function update(Request $request, $id)
    {
        $password = Password::where('user_id', Auth::id())->where('id', $id)->first();

        if (!$password) {
            return response()->json([
                'message' => 'Password not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'platform' => 'string|max:100',
            'email' => 'string|max:100',
            'password' => 'string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->category_id) {
            $categoryBelongsToUser = Auth::user()
                ->categories()
                ->where('id', $request->category_id)
                ->exists();

            if (!$categoryBelongsToUser) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }
        }
        $password->update($request->all());
        
        return response()->json($password);

    }

    public function destroy($id)
    {
        $password = Password::where('user_id', Auth::id())
        ->where('id', $id)
        ->first();

        if (!$password) {
            return response()->json([
                'message' => 'Password not found'
            ], 404);
        }

        $password->delete();

        return response()->json([
            'message' => 'Password deleted successfully'
        ]);

    }

    public function generatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'length' => 'required|integer|min:8|max:100',
            'uppercase_count' => 'required|integer|min:0',
            'number_count' => 'required|integer|min:0',
            'symbol_count' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $password = $this->passwordGenerator->generate(
            $request->length,
            $request->uppercase_count,
            $request->number_count,
            $request->symbol_count
        );

        return response()->json([
            'password' => $password
        ]);
    }

    public function passwordsByCategory($category_id)
    {
        try {
            // Validasi input category_id
            $validatedData = Validator::make(['category_id' => $category_id], [
                'category_id' => 'required|exists:categories,id'
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid category ID',
                    'errors' => $validatedData->errors()
                ], 400);
            }
    
            // Cek apakah kategori milik user yang sedang login
            $category = Category::findOrFail($category_id);
            if ($category->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access to this category'
                ], 403);
            }
    
            // Ambil password berdasarkan kategori
            $passwords = Password::where('user_id', Auth::id())
                ->where('category_id', $category_id)
                ->with('category')
                ->get();
    
            // Cek apakah ada password
            if ($passwords->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No passwords found in this category',
                    'data' => []
                ], 200);
            }
    
            // Respons berhasil dengan struktur yang lebih informatif
            return response()->json($passwords);
    
        } catch (\Exception $e) {
            // Tangani error yang tidak terduga
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
}