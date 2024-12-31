<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Password extends Model
{
    protected $fillable = [

        'user_id',
        'platform',
        'img_platform',
        'email',
        'password',
        'category_id'
    ];


    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}