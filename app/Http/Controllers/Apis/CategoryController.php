<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{

    /**
     * @OA\Get(
     *     path="/categories",
     *     summary="Get all categories for the authenticated user",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function get(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $categories = Category::where('user_id', auth()->user()->id)->get();
        return response()->json([
            'status' => true,
            'categories' => CategoryResource::collection($categories)
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create a new category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="New Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="category", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $validated = Validator::make($request->all(),[
            'name' => 'required|string|max:200',
        ]);
        if ($validated->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validated error',
                'erors' => $validated->errors()
            ], 401);
        }
        $category = Category::create([
            'name' => $request->name,
            'user_id' => auth()->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'category' => new CategoryResource($category)
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Update an existing category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(property="category", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $validated = Validator::make($request->all(),[
            'name' => 'required|string|max:200',
        ]);
        if ($validated->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validated error',
                'erors' => $validated->errors()
            ], 401);
        }
        $category = Category::where('id', $id)->where('user_id', $user->id)->first();
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }
        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'category' => new CategoryResource($category)
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Delete a category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $category = Category::where('id', $id)->where('user_id', $user->id)->first();
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }
        $user->balans -= $category->trAmount();
        $user->save();
        $category->delete();
        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
