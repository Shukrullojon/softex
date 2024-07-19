<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /**
     * @OA\Post(
     *     path="/avatar",
     *     summary="Create or update avatar from Base64 string",
     *     tags={"Avatar"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="image_base64", type="string", example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar saved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="avatar_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function createOrUpdateAvatar(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'image_base64' => 'required|string',
        ]);

        $image = $request->input('image_base64');
        $image_parts = explode(";base64,", $image);
        if (count($image_parts) !== 2) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid image format'
            ], 422);
        }

        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);

        $file_name = uniqid() . '.' . $image_type;
        $file_path = 'avatars/' . $file_name;

        Storage::disk('public')->put($file_path, $image_base64);

        $avatar_url = Storage::url($file_path);

        // Delete old avatar if exists
        if ($user->image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->image));
        }

        // Update user avatar URL in the database
        $user->image = $avatar_url;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Avatar saved successfully',
            'avatar_url' => $avatar_url,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/avatar",
     *     summary="Delete avatar",
     *     tags={"Avatar"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Avatar deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function deleteAvatar()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Delete old avatar if exists
        if ($user->image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->image));
            $user->image = null;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Avatar deleted successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No avatar to delete',
            ], 404);
        }
    }
}
