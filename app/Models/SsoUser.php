<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoUser extends Model
{
    protected $fillable = [
        'name',
        'email',
        'nim',
        'role',
        'jwt_token',
    ];
}