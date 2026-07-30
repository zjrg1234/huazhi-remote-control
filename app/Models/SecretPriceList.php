<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $number
 * @property integer $time
 * @property string $name
 * @property integer $price
 * @property string $created_at
 * @property string $updated_at
 */
class SecretPriceList extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'secret_price_list';

    /**
     * @var array
     */
    protected $fillable = ['number', 'time', 'name', 'price', 'created_at', 'updated_at'];
}
