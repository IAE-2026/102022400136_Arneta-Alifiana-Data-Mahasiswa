<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'activity_name',
        'log_content',
        'receipt_number',
        'status',
    ];
}