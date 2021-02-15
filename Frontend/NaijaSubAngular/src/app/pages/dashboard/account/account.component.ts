import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../../auth/auth.service";
import {UserService} from "../../../auth/user.service";
import {UiService} from "../../../common/ui.service";
import {Router} from "@angular/router";
import {environment} from "../../../../environments/environment.prod";

@Component({
    selector: 'app-account',
    templateUrl: './account.component.html',
    styleUrls: ['./account.component.scss']
})
export class AccountComponent implements OnInit {

    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    external_url = environment.external_api;

    providus_account_number = '';

    referral_link;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private router: Router
    ) {
        this.userDetails = authService.getUserObject();
        console.log(this.userDetails);
        if (this.userDetails.providus_account) {

            //providus data are saved in JSON format
            let providus_account = JSON.parse(this.userDetails.providus_account);

            this.providus_account_number = providus_account.account_number;
        }

        this.referral_link = environment.app_url + 'register/' + this.userDetails.username;//referral_id;
    }

    ngOnInit() {

        this.loading = true;

        this.fetchDashboard();
    }

    fetchDashboard() {

        this.userService.getDashboard().subscribe(
            (res: any) => {

                this.details = res.data;

                this.success = true;

                this.loading = false;
            },
            (error) => {
                this.loading = false;
                this.network = true;
                this.uiService.showToast('Internet connection error.');
            }
        );

    }

    onReload() {
        this.ngOnInit();
    }

    copyWalletId(walletId, description = 'Wallet id copied') {
        let inputValue = document.createElement('input');
        document.body.appendChild(inputValue);
        inputValue.value = walletId;
        inputValue.select();
        document.execCommand('copy', false);
        inputValue.remove();
        this.uiService.showToast(description);
    }

    onUpgrade() {
        this.router.navigate(['/dashboard/settings'], {queryParams: {account_upgrade: 'active'}});
    }
}
