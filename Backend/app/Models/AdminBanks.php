<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminBanks extends Model
{
    protected $table = 'admin_bank';
    protected $fillable = ['bank_name', 'account_name', 'status', 'account_number'];
}
