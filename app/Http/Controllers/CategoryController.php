<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;



class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
{
    try {
        $user_id = Auth::id();
        Log::info('User ID: ' . $user_id); // untuk debugging
    
        // Mengambil kategori berdasarkan user_id
        $categories = Category::where('user_id', $user_id)
            ->get(['id', 'user_id', 'category_name']); // Memilih kolom yang diperlukan
        
        // Mengubah format data agar sesuai dengan struktur JSON yang diinginkan
        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'user_id' => $category->user_id,
                'category_name' => $category->category_name,
            ];
        });

        // Mengembalikan data kategori langsung tanpa pembungkus 'categories'
        return response()->json($categories);  // Mengembalikan data langsung tanpa pembungkus
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $category = Category::create([
            'user_id' => Auth::id(),
            'category_name' => $request->category_name
        ]);

        return response()->json([ $category
        ], 201);
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $category = Category::where('user_id', Auth::id())->where('id', $id)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        $category->update([
            'category_name' => $request->category_name
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'category' => $category
        ]);

    }

    public function destroy($id)
    {
        $category = Category::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();
    
        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    
        // Cek apakah category adalah default
        if ($category->is_default) {
            return response()->json([
                'message' => 'Cannot delete default category'
            ], 403);
        }
    
        // Cek apakah ada password yang terkait
        $hasPasswords = Password::where('category_id', $id)->exists();
        if ($hasPasswords) {
            return response()->json([
                'message' => 'Cannot delete category that contains passwords'
            ], 403);
        }
    
        $category->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

}