import {ChangeDetectorRef, Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {FormBuilder} from "@angular/forms";

@Component({
    selector: 'app-wallet',
    templateUrl: './wallet.component.html',
    styleUrls: ['./wallet.component.scss']
})
export class WalletComponent implements OnInit {

    @ViewChild('closeWithdraw', {static: false}) closeWithdraw: ElementRef

    loading = false;

    network = false;

    success = false;

    profile: any;

    withdrawQuery = {amount: '', transaction_pin: ''};

    withdrawLoader = false;

    confirmPreview = false;

    confirmData = {amount: '', bank_name: '', account_name: '', account_number: ''};

    currentPin = '';

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private formBuilder: FormBuilder,
        private cdf: ChangeDetectorRef,
    ) {
    }

    ngOnInit() {
        this.loading = true;
        this.success = false;
        this.fetchWallet();
    }

    fetchWallet() {
        this.userService.withdraw({}).subscribe(
            (res: any) => {
                this.profile = res.data;
                this.success = true;
                this.loading = false;

            },
            (error) => {

                this.loading = false;

                this.network = false;

            }
        );
    }

    onRequestWithdrawal() {
        this.withdrawLoader = true;

        if (this.currentPin == '') {
            this.uiService.showToast('Pin is required');
            return;
        }

        this.withdrawQuery = {amount: this.confirmData.amount, transaction_pin: this.currentPin};

        this.userService.makeWithdraw(this.withdrawQuery).subscribe(
            (res: any) => {
                this.withdrawLoader = false;
                this.withdrawQuery = {amount: '', transaction_pin: ''};
                this.uiService.showToast('Bank Account has been credited successfully')
                this.ngOnInit();
                this.closeWithdraw.nativeElement.click();
            },
            (error) => {
                this.withdrawLoader = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );

    }

    confirmTransfer() {

        // check if amount entered is a valid amount for withdraw
        if (this.withdrawQuery.amount == '') {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.withdrawQuery.amount > this.profile.wallet.current_balance) {
            this.uiService.showToast('Insufficient wallet balance');
        }

        let data = this.authService.getUserObject();

        this.confirmData = {
            amount: this.withdrawQuery.amount,
            bank_name: data.account_bank,
            account_name: data.account_name,
            account_number: data.account_number
        };

        this.confirmPreview = !this.confirmPreview;
    }

    onChangePin(event) {

        let pin = event.target.value;

        this.currentPin = pin;

    }

    onReload() {
        this.ngOnInit();
    }
}
