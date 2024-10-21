<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Admin;
use App\Models\Publisher;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService){
        $this->authservice = $authService;
    }
  
    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string',
                'signup_type' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $user = $this->authService->createUser($request);

            if ($user) {
                $token = $user->createToken('User Token')->plainTextToken; 

                return $this->sendResponse(true, 'User successfully created', ['user' => $user, 'token' => $token,], 200);
            }

           
            return $this->sendResponse(false, 'Failed to register user', [], 400);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while registering user', ['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
            
            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->sendResponse(false, 'invalid credentials', [], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('User Token')->plainTextToken; 

            return $this->sendResponse(true, 'User Successfullt Logged in', [ 'user'=> $user, 'token'=>$token], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while logging user in', ['error' => $e->getMessage()], 500);
        }
    }

    public function adminRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
            
            if (Admin::where('email', $request->email)->exists()) {
                return $this->sendResponse(false, 'Email already exists', [], 400);
            }

            $admin = $this->authService->createAdmin($request);

            if ($admin) {
                $token = $admin->createToken('Admin Token')->plainTextToken;
                
                return $this->sendResponse(true, 'Admin successfully registered', ['admin'=>$admin, 'token'=>$token], 201);
            }

            return $this->sendResponse(false,'Failed to register admin', [], 400);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while registering Admin', ['error' => $e->getMessage()], 500);
        }
    }

    public function adminLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
            
            $admin = Admin::where('email', $request->email)->first();
            

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return $this->sendResponse(false, 'Invalid credentials', [], 401);
            }
           

            $token = $admin->createToken('Admin Token')->plainTextToken;
            
           
            return $this->sendResponse(true, 'Admin successfully logged in', ['admin'=>$admin, 'token'=>$token], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while logging user in', ['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully',
        ]);
    }
}
