<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FundingCharge extends Model
{
    protected $table = 'funding_charges';

    protected $fillable = ['type', 'amount'];
}
