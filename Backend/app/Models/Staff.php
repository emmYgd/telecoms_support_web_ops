<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Staff extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $primaryKey = 'sid';

    protected $table = 'staff';

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'status'];
}
