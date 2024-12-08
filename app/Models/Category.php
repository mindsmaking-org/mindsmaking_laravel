<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'position', 'image1', 'image2'];
    
    protected $hidden = ['created_at', 'updated_at'];
    
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
