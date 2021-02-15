<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Epin extends Model
{
    protected $table = 'e_pin';

    protected $primaryKey = 'epin';

    protected $fillable = ['tag', 'network_provider', 'amount', 'pin', 'serial', 'purchased_by', 'status', 'purchase_details', 'type'];


}
