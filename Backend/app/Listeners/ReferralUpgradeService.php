<?php

namespace App\Listeners;

use App\Http\Controllers\Service\General;

use App\Events\ReferralUpgrade;

use App\Log;
use App\MembershipPlan;
use App\User;
use App\Funding;


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReferralUpgradeService
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ReferralUpgrade $event
     * @return void
     */
    public function handle(ReferralUpgrade $event)
    {
        // fetch all direct upline

        $user_direct_up_line = User::where(['id' => $event->uid])->first();

        $fetch_upgrade_details = MembershipPlan::where(['id' => $event->upgrade_id])->first();

        // credit user bonus for the membership level
        $discount_amount = ($fetch_upgrade_details->discount_amount / 100) * $fetch_upgrade_details->upgrade_amount;

        $funding = new Funding();

//        credit bonus to wallet
        $funding->creditWallet($event->uid, $discount_amount);
        General::logActivities($event->uid, $discount_amount . ' bonus amount for upgrade to ' . $fetch_upgrade_details->name . ' has been credited to your wallet');

        // convert bonus coin and credit to wallet
        $convert_bonus_coin_credit = $fetch_upgrade_details->ns_coin_discount_amount / 2;
        $funding->creditWallet($event->uid, $convert_bonus_coin_credit);
        General::logActivities($event->uid, $fetch_upgrade_details->ns_coin_discount_amount . ' coin bonus amount for upgrade to ' . $fetch_upgrade_details->name . ' has been credited to your wallet');

        // calculate referral bonus for direct downline

        //$direct_up_line_details = User::where(['referral_id' => $user_direct_up_line->referral])->first();
        $direct_up_line_details = User::where(['username' => $user_direct_up_line->referral_upline_username])->first();
        if (!$direct_up_line_details)
            return;

        // update up_line with  referral bonus amount need
        $upgrade_referral_bonus = ($fetch_upgrade_details->direct_down_line_referral_commission / 100) * $fetch_upgrade_details->upgrade_amount;

        $funding->creditWallet($direct_up_line_details->id, $upgrade_referral_bonus);
        //General::logActivities($direct_up_line_details->id, $upgrade_referral_bonus . " referral bonus for membership upgrade to {$fetch_upgrade_details->name} by {$direct_up_line_details>first_name} {$direct_up_line_details->last_name} has been credited to your wallet");

        // update up_line for first indirect referral
        //$first_indirect_up_line_details = User::where(['referral_id' => $direct_up_line_details->referral])->first();
        $first_indirect_up_line_details = User::where(['username' => $direct_up_line_details->referral_upline_username])->first();
        if (!$first_indirect_up_line_details)
            return;

        // update up_line with  referral bonus amount need
        $upgrade_referral_bonus_indirect = ($fetch_upgrade_details->in_direct_down_line_referral_commission / 100) * $fetch_upgrade_details->upgrade_amount;

        $funding->creditWallet($first_indirect_up_line_details->id, $upgrade_referral_bonus_indirect);
        General::logActivities($first_indirect_up_line_details->id, $upgrade_referral_bonus_indirect . " referral bonus for membership upgrade to {$fetch_upgrade_details->name} by {$first_indirect_up_line_details->first_name} {$first_indirect_up_line_details->last_name} . ' has been credited to your wallet');

        $check_next_up_line = true;

        //$next_user_referral = $first_indirect_up_line_details->referral;
        referral_upline_username
        $other_generation_referral_bonus_up_line = explode(',', $fetch_upgrade_details->other_generation_commission);

        $i = 0;
        while ($check_next_up_line) {
            $i++;

            if (!$other_generation_referral_bonus_up_line[$i]) {
                $check_next_up_line = false;
                return;
            }


            //calculate other indirect upline
            //$next_up_line = User::where(['referral_id' => $next_user_referral])->first();
            $next_up_line = User::where(['username' => $next_user_referral])->first();
            if (!$next_up_line) {
                $check_next_up_line = false;
                return;
            }


            // update up_line with  referral bonus amount need
            $other_referral_bonus_indirect = ($other_generation_referral_bonus_up_line[$i] / 100) * $fetch_upgrade_details->upgrade_amount;

            $funding->creditWallet($next_up_line->id, $other_referral_bonus_indirect);
            General::logActivities($next_up_line->id, $other_referral_bonus_indirect . ' referral bonus for membership upgrade to ' . "{$fetch_upgrade_details->first_name} {$fetch_upgrade_details->last_name}" . ' by ' . "{$next_up_line->first_name} {$next_up_line->last_name} . ' has been credited to your wallet');

            //$next_user_referral = $next_up_line->referral;
            $next_user_referral = $next_up_line->referral_upline_username;
            
            $check_next_up_line = true;

        }
    }
}
