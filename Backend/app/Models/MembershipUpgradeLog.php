<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipUpgradeLog extends Model
{
    protected $table = 'membership_upgrade_log';

    protected $fillable = ['uid', 'current_plan', 'to_plan'];
}
