<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuserEnergyLog extends Model
{
    protected $table = 'cuser_energy_log';

    use HasFactory;
    protected $fillable = [
        'uid',
        'type',
        'type_name',
        'energy',
        'surplus_energy',
        'make_order_no',
        'venue',
        'recharge_amount',
        'time',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
