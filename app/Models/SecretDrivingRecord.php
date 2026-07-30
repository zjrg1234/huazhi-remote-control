<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $uid
 * @property string $show_id
 * @property integer $agent_id
 * @property string $user_name
 * @property integer $vehicle_id
 * @property string $vehicle_name
 * @property string $order_no
 * @property integer $reservation_status
 * @property integer $payment_amount
 * @property integer $start_time
 * @property integer $end_time
 * @property string $created_at
 * @property string $updated_at
 */
class SecretDrivingRecord extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'secret_driving_record';

    /**
     * @var array
     */
    protected $fillable = ['uid', 'show_id', 'agent_id', 'user_name', 'vehicle_id', 'vehicle_name', 'order_no', 'driving_status', 'payment_amount', 'receiver_id','transmitter_id','password_code','start_time', 'end_time', 'created_at', 'updated_at'];
}
