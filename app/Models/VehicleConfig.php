<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleConfig extends Model
{
    use HasFactory;
    protected $table = 'vehicle_config';

    protected $fillable = [
        'vehicle_id',
        'turn_direction',
        'turn_left',
        'turn_right',
        'oil_strength',
        'turn_strength',
        'oil_direction',
        'video_definition',
        'rear_camera_type',
        'operation_mode',
        'vehicle_config_detail',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
