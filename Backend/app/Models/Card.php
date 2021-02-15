<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'card';

    protected $fillable = ['uid', 'account_name', 'card_brand', 'card_number', 'card_expiry_month', 'card_expiry_year', 'card_cvv', 'card_token' ];
}
