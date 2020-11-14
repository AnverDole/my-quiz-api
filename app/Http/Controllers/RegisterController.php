<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /**
    * Register new user.
    * @param Illuminate\Http\Request
    * @return Illuminate\Http\Response
    */
    public function Register(Request $request){
        $data = (object)$request->validate([
            'email' => 'email|required|unique:users,email',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'password' => 'required|confirmed|min:8',
            'account_type' => ['required', 'string', Rule::in(User::ACCOUNT_INSTRUCTOR, User::ACCOUNT_STUDENT)],
        ]);

        $user = User::create([
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'email'  => $data->email,
            'password' => Hash::make($data->password),
            'account_type' => $data->account_type,
        ]);
        

        if($data->account_type == User::ACCOUNT_INSTRUCTOR){
            $user->Instructor()->create([]);
        }else{
            $user->Student()->create([]);
        }
       
        return response()->json([
            'logged' => true,
            'accessToken' => $user->createToken('Auth Token')->accessToken,
            'account_type' => $user->account_type,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
        ]);
    }
}
