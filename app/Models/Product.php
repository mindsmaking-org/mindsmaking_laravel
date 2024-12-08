<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image', 'title', 'description', 'price', 'links', 'reviews', 'parent_post_id', 'affiliate_posts', 'created_by'];

    protected $casts = [
        'description' => 'array',
        'links' => 'array',
        'reviews' => 'array',
        'affiliate_posts' => 'array',
    ];

}
