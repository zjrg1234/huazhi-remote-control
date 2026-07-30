<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $secret_name
 * @property integer $agent_id
 * @property integer $uid
 * @property string $agent_name
 * @property integer $second_agent_id
 * @property integer $is_first
 * @property integer $vehicle_id
 * @property string $vehicle_name
 * @property integer $is_valid
 * @property string $created_at
 * @property string $updated_at
 */
class ScretRecord extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'secret_record';

    /**
     * @var array
     */
    protected $fillable = ['secret_name', 'agent_id','secret_id', 'uid', 'agent_name', 'second_agent_id', 'is_first', 'vehicle_id', 'vehicle_name', 'is_valid','secret_num','show_id', 'created_at', 'updated_at'];
}
