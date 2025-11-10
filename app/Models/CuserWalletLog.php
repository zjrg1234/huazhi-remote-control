<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuserWalletLog extends Model
{
    protected $table = 'cuser_wallet_log';

    use HasFactory;

    protected $fillable = [
        'uid',
        'type',
        'type_name',
        'amount',
        'balance',
        'make_order_no',
        'venue',
        'make_user_name',
        'make_phone',
        'time',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
