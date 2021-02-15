<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transaction';

    protected $fillable = [
        'reference',
        'uid',
        'amount',
        'payment_reference',
        'description',
        'transaction_type',
        'status',
        'sub_service_id',
        'packages_id',
        'token',
        'other_banks',
        'sender_details',
        'spectrant_data',
        'coin_details'
    ];

    public function userDetails()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function fetchSubService()
    {
        return $this->hasOne(SubServices::class, 'sid', 'sub_service_id');
    }

    public function fetchPackages()
    {
        return $this->hasOne(Packages::class, 'pid', 'packages_id');
    }
}
