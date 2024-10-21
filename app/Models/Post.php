<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'header',
        'title', 
        'content',
        'is_verified',
        'category_id', 
        'subcategory_id', 
        'child_subcategory_id',
        'posted_by',
        'writer',
        'views'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function childSubcategory()
    {
        return $this->belongsTo(ChildSubcategory::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
