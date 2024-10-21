<?php

namespace App\Services;

use App\Models\Admin;

class SuperAdminService
{
    public function getAllAdmins(){
        return Admin::all();
    }

    public function findAdminById($adminId){
        return Admin::find($adminId);
    }
}