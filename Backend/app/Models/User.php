<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'first_name', /*'middle_name',*/ 'last_name', 'email', 'username', 'phone', 'state_of_residence', 'wallet_id', 'membership_level','gender', 'state_of_residence', 'bvn_verify', 'passport', 'bvn_data', 'providus_account'
    ];

    /**
     * The attributes that should be hidden for arrays.(mass assignment)...
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'email_verified_token', 'email_verified_status', '2fa' /*'account_type'*/
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    //in future, user can have many uplines and downlines
    public function referredUser()
    {
        return $this->hasMany(User::class, 'referral_upline_username', 'username');
    }
}
