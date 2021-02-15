<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['registration_bonus_amount', 'registration_bonus_status', 'referral_bonus_amount', 'max_referral', 'bank_charges'];
}
