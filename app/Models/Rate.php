<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = ['rating', 'action_user_id', 'rated_user_id'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function user_avg_rating(User $user)
    {
        if (DB::table('rates')->where('rated_user_id', $user->id)->count() == 0) {
            return response()->json(['message' => "This user hasn't been rated yet."]);
        } else {
            $userRating = DB::table('rates')->where('rated_user_id', $user->id)->avg('rating');
        }

        return round($userRating, 1);
    }
}
