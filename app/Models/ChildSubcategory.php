<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildSubcategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'subcategory_id'];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function Subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}
