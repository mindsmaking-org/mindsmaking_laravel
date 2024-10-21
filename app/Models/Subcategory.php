<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category_id'];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function childSubcategories()
    {
        return $this->hasMany(ChildSubcategory::class);
    }
}
