<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'table_of_content' ,
        'content',
        'images',
        'excerpt',
        'key_facts',
        'faq',
        'sources',
        'is_verified',
        'category_id', 
        'subcategory_id', 
        'child_subcategory_id',
        'posted_by',
        'writer',
        'views',
        'status'
    ];

    protected $casts = [
        'content' => 'json',
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
