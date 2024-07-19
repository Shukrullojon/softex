<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="date", type="string", format="date", example="2024-07-19"),
 *     @OA\Property(property="amount", type="number", format="float", example=100.00),
 *     @OA\Property(property="type", type="integer", example=1, enum={1, 2}),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-07-19T04:26:50Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-07-19T04:26:50Z"),
 * )
 */


class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $guarded = [];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that owns the transaction.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
