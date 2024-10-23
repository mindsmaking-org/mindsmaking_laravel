<?php

namespace App\Services;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function createUser($request)
    {
        return User::create([
            'name'=> $request->name,
            'email'=> $request->email,
            'password'=> Hash::make($request->password),
            'signup_type' => $request->signup_type,
            'signup_type_aspect' => $request->signup_type_aspect,
        ]);
    }

    public function createAdmin(array $data){
        return Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
    }
}