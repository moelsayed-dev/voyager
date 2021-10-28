<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Notifications\NewOffer;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferDeclined;
use App\Models\Offer;
use App\Models\Trip;


class OfferController extends Controller
{
    // Storing offers
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'trip_id' => ['required', 'numeric'],
                'offer_price' => ['required', 'numeric'],
                'number_of_seats_needed' => ['required', 'integer', 'min:1']
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()]);
        }

        // users can't make offers on their own trips.
        $trip = Trip::find($request['trip_id']);
        if (Auth::user()->id == $trip->user->id) {
            return response()->json(['success' => false, 'message' => 'you can\'t make an offer on your own trip.']);
        }

        // Checking if the user already made an offer on this trip.
        $previousOffer = DB::table('offers')->where([['user_id', '=', Auth::User()->id], ['trip_id', '=', $request['trip_id']]])->count();
        if ($previousOffer) {
            return response()->json(['success' => false, 'message' => 'You already made an offer on this trip.']);
        }

        // Checking if there are sufficient number of empty seats on the trip.
        if ($trip->number_of_empty_seats < $request['number_of_seats_needed']) {
            return response()->json(['success' => false, 'message' => 'Not enough empty seats on this trip.']);
        }

        $input = $request->all();
        $input['user_id'] = Auth::User()->id;
        $offer = Offer::create($input);



        // sending notifications to the user that a new offer was made on his trip.
        $notifiableUser = $offer->trip->user;
        $notifiableUser->notify(new NewOffer($offer->owner, $trip));

        return response()->json(['success' => true, 'data' => $offer], 201);
    }

    // Show offers for the logged in user
    public function show()
    {
        $user = Auth::user();
        $offers = Offer::where('user_id', Auth::User()->id)->orderBy('created_at', 'desc')->get();
        foreach ($offers as $offer) {
            $trip = $offer->trip;
            $trip['username'] = $offer->trip->user->name;
            $offer['trip'] = $trip;
        }
        return response()->json(['success' => true, 'offers' => $offers]);
    }

    public function update(Offer $offer, Request $request)
    {
        if ($offer->user_id != Auth::guard('sanctum')->id()) {
            return response()->json(['status' => 'Forbidden'], 403);
        }

        if ($offer->offer_status != 0) {
            return response()->json(['success' => false, 'message' => 'You cant edit this offer because the trip owner already responded to it.']);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'trip_id' => ['required', 'numeric'],
                'offer_price' => ['required', 'numeric'],
                'number_of_seats_needed' => ['required', 'integer', 'min:1']
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()]);
        }

        // Checking if there are sufficient number of empty seats on the trip.
        $trip = Trip::all()->find($request['trip_id']);
        if ($trip->number_of_empty_seats < $request['number_of_seats_needed']) {
            return response()->json(['success' => false, 'message' => 'Not enough empty seats on this trip.']);
        }

        $input = $request->all();
        $input['user_id'] = Auth::User()->id;
        $offer->update($input);

        return response()->json(['success' => true, 'data' => $offer]);
    }

    public function destroy(Offer $offer)
    {
        if ($offer->user_id != Auth::guard('sanctum')->id()) {
            return response()->json(['status' => 'Forbidden'], 403);
        }

        $offer->delete();
        if($offer->offer_status == 1) {
            $offer->trip['number_of_empty_seats'] += $offer['number_of_seats_needed'];
        }
        $offer->trip->trip_status = 0;
        $offer->trip->update();

        return response()->json(['success' => true, 'message' => 'Offer removed successfully.', 'data' => $offer], 200);
    }

    // accept or decline an offer
    public function respondToOffer(Offer $offer, Request $request)
    {
        if ($offer->trip->user->id != Auth::user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to respond to this offer.'
            ], 401);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'offer_status' => ['required', 'integer', 'min:1', 'max:2']
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()]);
        }

        if ($offer->offer_status == 1 or $offer->offer_status == 2) {
            return response()->json([
                'success' => false,
                'message' => 'You already responded to this offer.'
            ]);
        }

        $offer->offer_status = intval($request->offer_status);
        $offer->update();

        $user = $offer->owner;
        $trip = $offer->trip;

        // notifying the user and editing the trip info.
        if ($offer->offer_status == 1) {
            $user->notify(new OfferAccepted($offer->trip->user, $offer->trip));
            $trip['number_of_empty_seats'] -= $offer['number_of_seats_needed'];
            if ($trip['number_of_empty_seats'] == 0) {
                $trip->trip_status = 1;
            }
            $trip->update();

            // Now the two users can rate each other.
            DB::table('users_that_can_rate_one_another')->insertOrIgnore(['user1_id' => $user->id, 'user2_id' => $offer->trip->user->id]);

            return response()->json(['success' => 'true', 'message' => 'You accepted this offer.']);
        }

        if ($offer->offer_status == 2) {
            $user->notify(new OfferDeclined($offer->trip->user, $offer->trip));
            return response()->json(['success' => 'true', 'message' => 'You declined this offer.']);
        }
    }
}
