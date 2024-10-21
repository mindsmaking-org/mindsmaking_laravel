<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class BabyName extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'type',
        'origin',
        'theme',
        'culture',
        'country',
        'name',
        'meaning',
        'description',
        'views',
        'popular',
        'admin_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
