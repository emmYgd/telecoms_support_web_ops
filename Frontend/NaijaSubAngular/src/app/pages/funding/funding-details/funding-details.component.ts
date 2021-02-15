import {Component, Output, EventEmitter, Input, OnInit, OnChanges} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {UiService} from "../../../common/ui.service";
import {UserService} from "../../../auth/user.service";
import {AuthService} from "../../../auth/auth.service";
import {environment} from "../../../environments/environment.prod";

import {} from "../../";

declare var FlutterwaveCheckout: any;

declare var MonnifySDK: any;

@Component({
    selector: 'app-funding-details',
    templateUrl: './funding-details.component.html',
    styleUrls: ['./funding-details.component.scss']
})

export class FundingDetailsComponent implements OnInit {

    preview = false;

    loading = false;

    @Input() selectedMethod: any;

    @Output() fetchEvent = new EventEmitter<string>();

    fundWallet: FormGroup;

    selectedCard;

    selecteAirtimeFundingMethod;

    verifyWalletLoader = false;

    verifyWalletDetails: any;

    charges = 0;

    constructor(
        private formBuilder: FormBuilder,
        private uiService: UiService,
        private userService: UserService,
        private authService: AuthService
    ) {
    }

    ngOnInit() {
        this.preview = false;
        this.loading = false;
        this.selectedCard = undefined;
        this.fundWalletInit();
    }


    fundWalletInit() {
        this.fundWallet = this.formBuilder.group({
            funding_method: ['', [Validators.required]],
            amount: ['', [Validators.required]],
            payment_tnx: [''],
            user_receive: [''],
            card_id: [''],
            transaction_pin: [''],
            bank_id: [''],
            providus: [''],
            network: [''],
            sender_name: [''],
            sender_account_number: [''],
            airtime_mode: [''],
            phone_number: [''],
            airtime_pins: [''],
            sender_phone: [''],
            wallet_id: ['']
        });
    }

    manuelFunding() {

        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.fundWallet.get('bank_id').invalid) {
            this.uiService.showToast('Bank is required');
            return;
        }

        if (this.fundWallet.get('sender_name').invalid) {
            this.uiService.showToast('Sender Name is required');
            return;
        }

        if (this.fundWallet.get('sender_account_number').invalid) {
            this.uiService.showToast('Sender account number is required');
            return;
        }

        this.preview = !this.preview;
    }

    walletTransfer() {
        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.fundWallet.get('wallet_id').invalid) {
            this.uiService.showToast("Receiver's wallet ID is required");
            return;
        }

        this.verifyWalletLoader = true;
        this.userService.verifyWalletUser(this.fundWallet.value).subscribe(
            (res: any) => {
                this.verifyWalletLoader = false;
                this.uiService.showToast(res.message);
                this.verifyWalletDetails = res.data;
                this.preview = !this.preview;
            },
            (error) => {
                this.verifyWalletLoader = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    flutterFunding() {
        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        this.preview = !this.preview;
    }

    cardFunding() {
        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }
        if (this.selectedCard == 'undefined') {
            this.uiService.showToast('No card is selected-' + this.selectedCard);
            return;
        }

        this.preview = !this.preview;
    }

    providusFunding() {

        let successMessage = '';
        let loading = true;

        /*if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('The amount you paid for providus funding is required');
            return;
        }
        this.preview = !this.preview;*/

        successMessage = 'Successfully confirmed, pls refresh for the wallet funding to reflect';

        this.fundWallet.patchValue({
            funding_method: 'providus'
        });

        //connect with server: 
        let providusObservable = this.userService.fundWallet(this.fundWallet.value);

        //subscribe:
        providusObservable.subscribe(
            (res: any) => {

                this.loading = true;

                if(res.status == "success"){//check this on the backend

                    this.uiService.showToast(successMessage);

                    this.ngOnInit();

                    this.onFetchEvent();

                }

            },
            (error) => {
                this.loading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );

        
    }

    onChangePin(event) {
        let pin = event.target.value;
        this.fundWallet.patchValue({
            transaction_pin: pin
        });

    }

    onSelectCard(i) {
        this.selectedCard = i;
    }

    async onConfirm() {

        if (this.selectedMethod[0].name == 'manuel') {
            this.funding();
        } else if (this.selectedMethod[0].name == 'flutter') {
            await this.onPayment();
        } else if (this.selectedMethod[0].name == 'cards') {
            this.funding();
        } else if (this.selectedMethod[0].name == 'pay_airtime') {
            this.funding();
        }else if (this.selectedMethod[0].name == 'transfer') {
            this.funding();
        }

    }

    funding(tx = '') {

        let successMessage = '';
        this.loading = true;

        if (this.fundWallet.get('transaction_pin').invalid) {
            this.uiService.showToast('4 digit transaction pin is required.');
            return;
        }

        if (this.selectedMethod[0].name == 'manuel') {
            this.fundWallet.patchValue({
                funding_method: 'manual',
                bank_id: this.selectedMethod[1].available_banks[this.fundWallet.get('bank_id').value]
            });

            successMessage = 'Successfully requested funding.';
        }

        if (this.selectedMethod[0].name == 'flutter') {

            this.fundWallet.patchValue({
                funding_method: 'flutter_verify',
                payment_tnx: 
            });

            successMessage = 'Account funding successful';
        }

        if (this.selectedMethod[0].name == 'cards') {
            this.fundWallet.patchValue({
                funding_method: 'flutter_card',
                card_id: this.selectedMethod[1].cards[this.selectedCard].id
            });

            successMessage = 'Account funding successful';
        }

        /*if (this.selectedMethod[0].name == 'providus') {
            this.fundWallet.patchValue({
                funding_method: 'providus',
                providus: tx
            });

            successMessage = 'Account funding successful';
        }*/

        if (this.selectedMethod[0].name == 'pay_airtime') {
            this.fundWallet.patchValue({
                funding_method: 'pay_airtime'
            });
            successMessage = 'Account would be funded in few minutes time.';
        }

        if (this.selectedMethod[0].name == 'transfer') {
            this.fundWallet.patchValue({
                funding_method: 'transfer'
            });
            successMessage = 'Transfer successful.';
        }

        this.userService.fundWallet(this.fundWallet.value).subscribe(
            (res: any) => {

                this.loading = true;

                this.uiService.showToast(successMessage);

                this.ngOnInit();

                this.onFetchEvent();

            },
            (error) => {
                this.loading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );


    }

    async onPayment() {
        let response;
        await FlutterwaveCheckout({
            public_key: environment.flutter_wave,
            tx_ref: 'flutter_pay' + (Math.random() * 10),
            amount: this.fundWallet.get('amount').value,
            currency: "NGN",
            payment_options: "card",
            customer: {
                email: this.authService.getUserObject()['email'],
                phone_number: this.authService.getUserObject()['phone'],
                name: this.authService.getUserObject()['name'],
            },
            callback: async (data) => {

                this.uiService.showToast('Allow page reload to ensure funding was successful.');
                this.funding(data.tx_ref);
            },
            customizations: {
                title: "Wallet funding",
            },
            close: () => {
                alert('Payment closed')
            }
        });

        return response;
    }

    onFetchEvent() {
        this.fetchEvent.emit('true');
    }

    airtimeFunding() {

        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.fundWallet.get('network').invalid) {
            this.uiService.showToast('Mobile Network is required');
            return;
        }

        if (this.fundWallet.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.fundWallet.get('sender_phone').invalid) {
            this.uiService.showToast('Sender phone number is required');
            return;
        }

        if (this.selecteAirtimeFundingMethod == 'pin') {
            if (this.fundWallet.get('airtime_pins').invalid) {
                this.uiService.showToast('Airtime pin for funding are required');
                return;
            }

        } else if (this.selecteAirtimeFundingMethod == 'share_n_sell') {

        } else {
            this.uiService.showToast('No Airtime mode selected');
            return;
        }

        this.preview = !this.preview;
    }

    onChangeAirtimePaymentMethod(event) {

        this.selecteAirtimeFundingMethod = event.target.value;

    }

    calculateCharges(data) {
        data.map((e) => {
            if (e.type.toLowerCase() == this.selectedMethod[0].name) {
                this.charges = e.amount;
            }
        });
        return;
    }
}
