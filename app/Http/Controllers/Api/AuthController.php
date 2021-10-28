<?php

namespace App\Http\Controllers\Api;

use Laravel\Sanctum\HasApiTokens;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    use HasApiTokens;
    public $successStatus = 200;

    public function register(Request $request)
    {
        $validator = Validator::make(
                $request->all(),
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                    'phone_number' => ['nullable', 'regex:/(01)[0-9]{9}/', 'size:11', 'unique:users'],
                    'gender' => ['required', 'string'],
                    'date_of_birth' => ['required', 'date'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                    'avatar' => ['nullable', 'file', 'image']
                ]
            );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $input = $request->all();
        // uploading profile picture.
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            if (!$file->isValid()) {
                return response()->json(['invalid profile picture.'], 400);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $path = Storage::url($path);
            $input['avatar'] = $path;

            // $path = public_path('/uploads/avatars/');
            // $file->move($path, $file->getClientOriginalName());
            // $avatar = asset($file->getClientOriginalName());
            // $avatar = 'uploads/avatars/' . $file->getClientOriginalName();
            // $input['avatar'] = $avatar;
        }

        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $token = $user->createToken('AppName')->plainTextToken;
        return response()->json([
            'success' => 'true',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $token =  $user->createToken($user->name)->plainTextToken;
            return response()->json(['token' => $token, 'user' => $user], 200);
        } else {
            return response()->json(['error' => 'Wrong Credentials'], 401);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($request['email'] == $user->email) {
            $request->request->remove('email');
        }
        if ($request['phone_number'] == $user->phone_number) {
            $request->request->remove('phone_number');
        }
        if (empty($request['name'])) {
            $request->request->remove('name');
        }
        if (empty($request['password'])) {
            $request->request->remove('password');
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => ['nullable', 'string', 'max:255'],
                'email' => ['string', 'email', 'max:255', 'unique:users'],
                'phone_number' => ['nullable', 'regex:/(01)[0-9]{9}/', 'size:11', 'unique:users'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'avatar' => ['nullable', 'file', 'image']
            ]
        );
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }


        $input = $request->all();

        // uploading profile picture.
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            if (!$file->isValid()) {
                return response()->json(['invalid profile picture.'], 400);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $path = Storage::url($path);
            $input['avatar'] = $path;

            // $path = public_path('/uploads/avatars/');
            // $file->move($path, $file->getClientOriginalName());
            // $avatar = 'uploads/avatars/' . $file->getClientOriginalName();
            // $input['avatar'] = $avatar;
        }
        /* XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX */
        if ($request->has('password')) {
            $input['password'] = bcrypt($input['password']);
        }
        $user->update($input);
        $user->avatar = asset($user->avatar);

        return response()->json(['success' => true, 'message' => "Your information have been updated", 'data' => $user]);
    }

    // fetch logged in user details
    public function getUser()
    {
        $user = Auth::user();
        $user->avatar = asset($user->avatar);
        // $user['trips'] = $user->trips;
        return response()->json(['user' => $user], 200);
    }

    // User Profile
    public function profile(User $user)
    {
        $user->avatar = asset($user->avatar);
        return response()->json(['User Data' => $user], 200);
    }

    // Logout from current device
    public function logout()
    {
        if (Auth::user()) {
            Auth::user()->currentAccessToken()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Successfuly logged out.'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to logout.'
            ], 401);
        }
    }

    // Logout from all connected devices
    public function logoutFromAllDevices()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Successfuly logged out from all devices.'], 200);
    }

}
