<?php

namespace App\Listeners;

use App\Events\UserDownLine;

use App\Log;
use App\User;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserDownLineServices
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
     * @param object $event
     * @return void
     * @throws \Exception
     */
    public function handle(UserDownLine $event)
    {
        $current_list = [];

        $referral_id = $event->referral_id;

        $referred = $event->referred;

        // this cycle would run till the 15th level so as to check if user has down line to that end

        // fetch referred user by the referral
        $referral_referred = array_column(User::where(['referral' => $referral_id])->get(['referral_id'])->toArray(), 'referral_id');

        if (count($referral_referred) < 3)
            return;

        // this loop would run if and only if the number of referred attained is above 3
        $next_down_line_check = [];

        for ($i = 0; $i < count($referral_referred); $i++) {

            // calculating who to spill to we would check if the first referred has less than three if not move to second , else move to third keeping track of there downlines for feature checks

            $direct_down_line_check = array_column(User::where(['referral' => $referral_referred[$i]])->get(['referral_id'])->toArray(), 'referral_id');

            if (count($direct_down_line_check) < 3) {

                User::where(['referral_id' => $referred])->update([
                    'referral' => $referral_referred[$i]
                ]);

                return;
            } else if (count($direct_down_line_check) == 3) {

                // fetch are thw down line for this up-line and add to the next down line check array

                for ($d = 0; $d < count($direct_down_line_check); $d++) {
                    array_push($next_down_line_check, $direct_down_line_check[$d]);
                }

            } else {

            }

        }

        while (count($next_down_line_check) !== 0) {

            for ($i = 0; $i < count($next_down_line_check); $i++) {

                // calculating who to spill to we would check if the first referred has less than three if not move to second , else move to third keeping track of there downlines for feature checks
                $direct_down_line_check = array_column(User::where(['referral' => $next_down_line_check[$i]])->get(['referral_id'])->toArray(), 'referral_id');

                if (count($direct_down_line_check) < 3) {

                    User::where(['referral_id' => $referred])->update([
                        'referral' => $next_down_line_check[$i]
                    ]);

                    return;

                } else if (count($direct_down_line_check) == 3) {

                    // fetch a;; down line for this up-line and add to the next down line check array

                    for ($d = 0; $d < count($direct_down_line_check); $d++) {
                        array_push($next_down_line_check, $direct_down_line_check[$d]);
                    }
                }

            }
        }
    }
}
