<?php

namespace App\Services;

use App\Models\Activity;

class ActivityService
{
    public function createActivity($email, $title, $action)
    {
        return Activity::create([
            'admin_email' => $email,
            'title' => $title,
            'action' => $action,
            'ip_address' => request()->ip(), 
            'user_agent' => request()->header('User-Agent'), 
        ]);
    }


}