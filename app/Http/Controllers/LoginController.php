<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function Login(Request $request){
        $data = (object)$request->validate([
            'email' => 'email|required|exists:users,email',
            'password' => 'required',
        ]);
        $user = User::where('email', '=', $data->email)->first();
        
        if(Hash::check($data->password, $user->password)){
            return response()->json([
                'logged' => true,
                'accessToken' => $user->createToken('Auth Token')->accessToken,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'account_type' => $user->account_type,
                'email' => $user->email,
            ]);
        }else{
           throw ValidationException::withMessages([
               'email' => 'You have entered an invalid username or password'
           ]);
        }
    }
    public function Logout(Request $request){
        $request->user()->tokens()->delete();
    }
}
