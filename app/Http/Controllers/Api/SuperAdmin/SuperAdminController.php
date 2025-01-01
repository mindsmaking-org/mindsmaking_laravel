<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SuperAdminService;
use App\Services\ActivityService;

class SuperAdminController extends Controller
{
    protected $superadminService;
    protected $activityService;

    
    public function __construct(SuperAdminService $superadminService, ActivityService $activityService){
        $this->superadminService = $superadminService;
        $this->activityService = $activityService;
    }

    public function getAllAdmins(Request $request)
    {
        try {
            // Retrieve query parameters
            $adminEmail = $request->query('email');
            $adminId = $request->query('id');
    
            // Fetch admin by email
            if ($adminEmail) {
                $admins = $this->superadminService->findAdminByEmail($adminEmail);
    
                if (!$admins) {
                    return $this->sendResponse(false, 'Failed to fetch admin by email.', [], 400);
                }
    
                return $this->sendResponse(true, 'Successfully fetched admin by email.', ['data' => $admins], 200);
            }
    
            // Fetch admin by ID
            if ($adminId) {
                $admins = $this->superadminService->findAdminById($adminId);
    
                if (!$admins) {
                    return $this->sendResponse(false, 'Failed to fetch admin by ID.', [], 400);
                }
    
                return $this->sendResponse(true, 'Successfully fetched admin by ID.', ['data' => $admins], 200);
            }
    
            // Fetch all admins excluding super admin
            $admins = $this->superadminService->getAllAdmins()
                ->where('email', '!=', 'superAdmin@gmail.com');
    
            if ($admins->isEmpty()) {
                return $this->sendResponse(false, 'No admins found in the database.', [], 400);
            }
    
            return $this->sendResponse(true, 'Successfully fetched all admins.', ['data' => $admins], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching admins.', ['error' => $e->getMessage()], 500);
        }
    }
    

    // public function updateAdminRoles
    public function updateAdminRoles(Request $request){
        try {
            $adminId = $request->route('adminId');
            $newRoles = $request->input('roles');

            if(!is_numeric($adminId)){
                return $this->SendResponse(false, 'Admin Id has to be a type of INT', [], 400);
            }

            if (empty($newRoles)) {
                return $this->sendResponse(false, 'Roles cannot be empty', [], 400);
            }

            $admin = $this->superadminService->findAdminById($adminId);

            if(!$admin){
                return $this->sendResponse(false, 'No admin was found with the given Id', [], 400);
            }

            $newRolesArray = array_map('trim', explode(',', $newRoles));

            $existingRolesArray = array_map('trim', explode(',', $admin->roles));

            $updatedRolesArray = array_unique(array_merge($existingRolesArray, $newRolesArray));

            $updatedRoles = implode(',', $updatedRolesArray);

            $admin->roles = $updatedRoles;
            $admin->save();

            return $this->sendResponse(true, 'Admin roles updated successfully', ['roles' => $updatedRoles], 200);
        }catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while trying to update an admin role.', ['error' => $e->getMessage()], 500);
        }
    }
}
