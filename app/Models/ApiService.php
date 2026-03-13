<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiService extends Model
{
    protected $guarded = [];
    public function tokenTypes() 
    {
        return $this->belongsToMany(TokenType::class);
    }
}
