<?php

namespace App\Services;

use App\Models\Admin;


class WriterService
{

    public function findWriterByName($name)
    {
        return Admin::where('name', 'like', "%{$name}%")
            ->whereRaw("FIND_IN_SET('writer', roles)")
            ->get();
    }
    

    public function findWriterByEmail($email){
        return Admin::where('email','like', "%{$email}%")
        ->whereRaw("FIND_IN_SET('writer', roles)")
        ->get();
    }


    public function allWrtiters(){
        return Admin::whereRaw("FIND_IN_SET('writer', roles)")->get();
    }

}