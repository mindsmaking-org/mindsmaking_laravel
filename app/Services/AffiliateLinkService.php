<?php

namespace App\Services;

use App\Models\AffiliateLink;

class AffiliateLinkService
{
    public function checkForExistingAffiliate(array $validatedData){
        return AffiliateLink::where('name', $validatedData['name'])
         ->first();
    }

    public function createAffiliate(array $data){
        return AffiliateLink::create($data);
    }

    public function findAffiliateById($id){
        return AffiliateLink::find($id);
    }

    public function findAffiliateByName($name){
        return AffiliateLink::where('name', 'like', "%{$name}%")->get();
    }

    public function allAffiliate(){
        return AffiliateLink::all();
    }
}