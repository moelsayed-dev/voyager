<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;


class RateController extends Controller
{
    public function store(Request $request)
    {
        // checking if these two users are allowed to rate each other.
        $canRate = DB::table('users_that_can_rate_one_another')->where([['user1_id', '=', Auth::user()->id], ['user2_id', '=', $request['rated_user_id']]])
        ->orWhere([['user1_id', '=', $request['rated_user_id']], ['user2_id', '=', Auth::user()->id]])->count();

        if ($canRate == 0) {
            return response()->json(['success' => false, 'message' => 'You cant rate this user']);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'rating' => ['required', 'min:1', 'max:5'],
                'rated_user_id' => ['required', 'integer'],
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'Validation failure', 'errors' => $validator->errors()]);
        }


        // Checking if the logged in user has rated the other user before.
        $previousRate = DB::table('rates')->where([['action_user_id', '=', Auth::user()->id], ['rated_user_id', '=', $request['rated_user_id']]]);
        if ($previousRate->count()) {
            $previousRate->delete();
        }

        $input = $request->all();
        $input['action_user_id'] = Auth::user()->id;

        $rating = Rate::create($input);
        return response()->json(['success' => true, 'message' => 'Thanks for the feedback!']);
    }

    // calculate the average rating of a specific user to show it on their profile.
    public function user_avg_rating(User $user)
    {
        $rate = new Rate();
        return response()->json(['rating' => round($rate->user_avg_rating($user), 1)]);
    }


    // public function user_avg_rating(User $user)
    // {
    //     if (DB::table('rates')->where('rated_user_id', $user->id)->count() == 0) {
    //         return response()->json(['message' => "This user hasn't been rated yet."]);
    //     } else {
    //         $userRating = DB::table('rates')->where('rated_user_id', $user->id)->avg('rating');
    //     }

    //     return response()->json(['rating' => round($userRating, 1)]);
    // }
}
