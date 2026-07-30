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
        'video_definition',
        'rear_camera_type',
        'vehicle_config_detail',
        'camera_type',
        'default_camera_clarity',
        'auto_easy_operation_value',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
