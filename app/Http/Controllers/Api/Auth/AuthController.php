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
use Illuminate\Support\Facades\Storage;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService){
        $this->authService = $authService;
    }
  
    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'profile_pic' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'password' => 'required|string',
                'signup_type' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $profilePicPath = null;
            if ($request->hasFile('profile_pic')) {
                $profilePic = $request->file('profile_pic');
                $profilePicPath = $profilePic->store('images/profile/users', 'public'); 
            }

            $userData = $request->all();
            $userData['image'] = $profilePicPath;

            $user = $this->authService->createUser($userData);

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
                'profile_pic' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $profilePicPath = null;
            if ($request->hasFile('profile_pic')) {
                $profilePic = $request->file('profile_pic');
                $profilePicPath = $profilePic->store('images/profile/admins', 'public'); 
            }

            $adminData = $request->all();
            $adminData['image'] = $profilePicPath;
            
            if (Admin::where('email', $request->email)->exists()) {
                return $this->sendResponse(false, 'Email already exists', [], 400);
            }

            $admin = $this->authService->createAdmin($adminData);

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

    public function editUser(Request $request)
    {
        try {
            $user = auth()->user();
    
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);
    
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
    
            // Handle profile picture upload
            if ($request->hasFile('profile_pic')) {
                $profilePic = $request->file('profile_pic');
                $profilePicPath = $profilePic->store('images/profile/users', 'public');
    
                // Delete old image if exists
                if ($user->image) {
                    \Storage::disk('public')->delete($user->image);
                }
    
                $user->image = $profilePicPath;
            }
    
            // Update name if provided
            if ($request->filled('name')) {
                $user->name = $request->input('name');
            }
    
            // Check if no fields were updated
            if (!$request->hasFile('profile_pic') && !$request->filled('name')) {
                return $this->sendResponse(false, 'No fields were provided for update', [], 400);
            }
    
            // Save user details
            $user->save();
    
            return $this->sendResponse(true, 'User details updated successfully', ['user' => $user], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating the user details', ['error' => $e->getMessage()], 500);
        }
    }
    
    public function editAdmin(Request $request)
    {
        try {
            $admin = auth()->user(); 
    
            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);
    
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
    
            // Handle profile picture upload
            if ($request->hasFile('profile_pic')) {
                $profilePic = $request->file('profile_pic');
                $profilePicPath = $profilePic->store('images/profile/admins', 'public');
    
                // Delete old image if exists
                if ($admin->image) {
                    \Storage::disk('public')->delete($admin->image);
                }
    
                $admin->image = $profilePicPath;
            }
    
            // Update name if provided
            if ($request->filled('name')) {
                $admin->name = $request->input('name');
            }
    
            // Check if no fields were updated
            if (!$request->hasFile('profile_pic') && !$request->filled('name')) {
                return $this->sendResponse(false, 'No fields were provided for update', [], 400);
            }
    
            // Save changes
            $admin->save();
    
            return $this->sendResponse(true, 'Admin details updated successfully', ['admin' => $admin], 200);
    
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating admin details', ['error' => $e->getMessage()], 500);
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
