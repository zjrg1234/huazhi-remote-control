<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuserAgent extends Model
{
    protected $table='cuser_agent';
    use HasFactory;
    protected $fillable = [
        'uid',
        'agent_name',
        'level',
        'phone_number',
        'venue_quantity',
        'create_site_quantity',
        'is_support',
        'head_shot',
        'provinces',
        'city',
        'register_time',
        'review_status',
        'support_status',
        'is_cancel',
        'sorting',
        'yesterday_turnover'
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
