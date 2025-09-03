<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'email',
        'access_token',
        'refresh_token',
    ];
}


