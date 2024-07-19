<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     required={"id", "name", "user_id", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Example Category"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-07-19T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-07-19T00:00:00Z")
 * )
 */

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $guarded = [];

    /**
     * Get the user that owns the category.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the category.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function trAmount()
    {
        return $this->hasOne(Transaction::class)->where('user_id',auth()->user()->id)->sum('amount');
    }
}
