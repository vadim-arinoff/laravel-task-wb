<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenType extends Model
{
    protected $guarded = [];
    public function apiServices()
    {
        return $this->belongsToMany(ApiService::class);
    }
}
