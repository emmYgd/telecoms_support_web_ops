import {ChangeDetectorRef, Component, OnInit} from '@angular/core';
import {environment} from "../../environments/environment.prod";
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {ActivatedRoute, Router} from "@angular/router";
import {Location} from "@angular/common";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {Subscription} from "rxjs";

@Component({
    selector: 'app-services-details',
    templateUrl: './services-details.component.html',
    styleUrls: ['./services-details.component.scss']
})
export class ServicesDetailsComponent implements OnInit {

    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    external_url = environment.external_api;

    service;

    services: FormGroup;

    preview = false;

    onConfirmLoading = false;

    purchaseLoading = false;

    selectedSubServiceDetails;

    extraDetails: any;

    currentPin;

    plans;

    selectedPlan: any;

    coinService = false;

    onServiceTypeSelected = false;

    coinServiceTypeSelected = {type: '', amount: '', btc_rate: '0.001'}

    sellingBtcRate = 0;

    buyingBtcRate = 0;

    subscribe_e_pin = false;

    subscribe_fee = false;

    subscribeLoader = false;

    ePinQuery = {amount: '', network: '', purchase_count: '', type: ''};

    buyEPinLoader = false;

    coinQuery = {amount: '', transaction_pin: '', wallet_address: ''};

    coinDetailSell: any;

    userWalletAddress;

    currentRate = 0;

    nairaRate = 0;

    coinLoading = false;

    dollarRateSelling = 0;

    dollarRateBuying = 0;

    subscription: Subscription;

    customerDetailsElectricity;

    selectAirtimeFundingMethod;

    selectedAirtimeService = 'buy';

    airtimeServiceInformation = false;

    customerInternetServiceDetails;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private cdf: ChangeDetectorRef,
        private activatedRoute: ActivatedRoute,
        private location: Location,
        private formBuilder: FormBuilder,
        private router: Router
    ) {
        this.userDetails = authService.getUserObject();
        this.service = activatedRoute.params['value'].id

        this.userWalletAddress = this.userDetails.wallet_address_coin ? this.userDetails.wallet_address_coin : '';

    }

    ngOnInit() {
        this.activatedRoute.params.subscribe(routeParams => {
            this.service = this.activatedRoute.params['value'].id;
            this.loading = true;
            this.network = false
            this.success = false;
            this.preview = false;
            this.onConfirmLoading = false;
            this.currentPin = '';
            this.servicesInit();
            this.fetchServices();
        });
    }

    onChangeService(event) {
        this.service = event.target.value;
        this.router.navigate(['/dashboard/purchase/' + event.target.value]);
        this.ngOnInit();
    }

    fetchServices() {

        if (this.service == 'bill_payment') {
            this.loading = false;
            this.success = true;
            return;
        }

        this.userService.fetchSubServices({service_id: this.service}).subscribe(
            (res: any) => {

                this.details = res.data;

                this.dollarRateSelling = this.details.dollar_rate_selling;

                this.dollarRateBuying = this.details.dollar_rate_buying;

                this.success = true;

                this.loading = false;

                this.services.patchValue({
                    service_type: this.details.services.display_name.toLowerCase(),
                    service_id: this.service
                });

            },
            (error) => {
                this.loading = false;
                this.network = true;
                this.uiService.showToast('Internet connection error.');
            }
        );

    }

    onBack() {
        this.location.back();
    }

    onReload() {
        this.ngOnInit();
    }

    servicesInit() {
        this.services = this.formBuilder.group({
            service_id: ['', [Validators.required]],
            amount: ['', [Validators.required]],
            sub_service: ['', [Validators.required]],
            service_number: ['', [Validators.required]],
            service_type: ['', [Validators.required]],
            plan: ['', [Validators.required]],
            electricity_type: [''],
            airtime_product_id: [''],
            airtime_mode: [''],
            phone_number: [''],
            network: [''],
            airtime_pins: ['']
        });
    }

    onConfirm() {

        if (this.selectedAirtimeService == 'sell' && this.details.services.display_name == 'airtime') {
            this.preview = !this.preview;
            return;
        }

        if (this.services.get('amount').invalid) {
            this.uiService.showToast('Amount is required');
            return;
        }

        if (this.services.get('service_number').invalid) {
            this.uiService.showToast('Phone Number is required');
            return;
        }
        if (this.services.get('sub_service').invalid) {
            this.uiService.showToast('No service selected');
            return;
        }

        this.onConfirmLoading = true;

        this.userService.initTransaction(this.services.value).subscribe(
            (res: any) => {

                this.onConfirmLoading = false;

                if (!res.data.response) {
                    this.uiService.showToast('An error occurred confirming service.');
                    return;
                }

                if (res.data.response.validate == true) {
                    this.preview = !this.preview;
                    this.selectedSubServiceDetails = res.data.sub_service_details;
                    this.extraDetails = res.data.response;

                    if (this.details.services.display_name.toLowerCase() == 'airtime') {
                        this.services.patchValue({
                            airtime_product_id: res.data.response.extra_data.product_id_api
                        });
                    }

                    if (this.details.services.display_name.toLowerCase() == 'electricity') {
                        this.customerDetailsElectricity = res.data.response.extra_data;
                    }

                    if (this.details.services.display_name.toLowerCase() == 'internet_service') {
                        this.customerInternetServiceDetails = res.data.response.extra_data;
                    }

                } else {
                    this.uiService.showToast('Kindly ensure service number provided is valid')
                }
            },
            (error) => {
                this.onConfirmLoading = false;
                this.uiService.showToast('Internet connection error.');
            }
        );
    }

    onPurchase() {

        if (this.currentPin == '') {
            this.uiService.showToast('Transaction pin is required');
            return;
        }

        switch (this.details.services.display_name.toLowerCase()) {

            case 'airtime':
                if (this.selectedAirtimeService === 'buy') {
                    let query = {
                        phone_number: this.services.get('service_number').value,
                        amount: this.services.get('amount').value,
                        service_id: this.services.get('sub_service').value,
                        transaction_pin: this.currentPin,
                        product_code: this.services.get('airtime_product_id').value
                    }

                    this.onAirtimePurchase(query);
                }

                if (this.selectedAirtimeService === 'sell') {
                    let query = {
                        amount: this.services.get('amount').value,
                        type: this.services.get('airtime_mode').value,
                        pin: this.services.get('airtime_pins').value,
                        network: this.services.get('network').value,
                        transaction_pin: this.currentPin,
                        phone: this.services.get('phone_number').value,
                        service_id: this.details.services.id
                    };
                    this.onAirtimeSell(query);
                }
                break;
            case 'data':
                let queryData = {
                    phone_number: this.services.get('service_number').value,
                    package_id: this.services.get('plan').value,
                    transaction_pin: this.currentPin
                }
                this.onDataPurchase(queryData);
                break;
            case 'internet_service':
                let queryInternetService = {
                    phone_number: this.services.get('service_number').value,
                    package_id: this.services.get('plan').value,
                    transaction_pin: this.currentPin
                }
                this.onInternetPurchase(queryInternetService);
                break;
            case 'cable':
                let queryCable = {
                    smart_card_number: this.services.get('service_number').value,
                    package_id: this.services.get('plan').value,
                    transaction_pin: this.currentPin
                };
                this.onCablePurchase(queryCable);
                break;
            case 'electricity':
                let queryElectricity = {
                    meter_number: this.services.get('service_number').value,
                    service_id: this.services.get('sub_service').value,
                    amount: this.services.get('amount').value,
                    transaction_pin: this.currentPin
                };
                this.onElectricityPurchase(queryElectricity);
                break;
            case 'coin':
                if (this.coinServiceTypeSelected.type == 'sell') {
                    this.sellCoin();
                }

                if (this.coinServiceTypeSelected.type == 'buy') {
                    this.buyCoin();
                }

                break;
            default:
                this.uiService.showToast('Service selected is not a valid service');
        }
    }

    onAirtimePurchase(query) {
        this.purchaseLoading = true;
        this.userService.airtimePurchase(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased airtime');
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onAirtimeSell(query) {
        this.purchaseLoading = true;
        this.userService.sellAirtime(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased airtime');
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onDataPurchase(query) {
        this.purchaseLoading = true;
        this.userService.dataPurchase(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased data');
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onInternetPurchase(query) {
        this.purchaseLoading = true;
        this.userService.internetPurchase(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased internet service');
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onCablePurchase(query) {
        this.purchaseLoading = true;
        this.userService.cablePurchase(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased cable');
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onElectricityPurchase(query) {
        this.purchaseLoading = true;
        this.userService.electricityPurchase(query).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast('Successfully purchased electricity service');
                this.router.navigate(['/dashboard/transaction-details/' + res.data.token.reference]);
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    onSelectNetwork(event) {

        let service_id = event.target.value;
        this.services.patchValue({
            plan: '',
            amount: ''
        });
        for (let i = 0; i < this.details.sub_service.length; i++) {

            if (service_id == this.details.sub_service[i].sid) {
                this.plans = this.details.sub_service[i].fetch_packages
                if (!this.plans) {
                    return
                }
                this.services.patchValue({
                    plan: this.plans[0].pid,
                    amount: this.plans[0].amount
                });
                this.selectedPlan = this.plans[0]
            }

        }



    }

    onSelectCableService(event) {

        let service_id = event.target.value;
        this.services.patchValue({
            plan: '',
            amount: ''
        });
        for (let i = 0; i < this.details.sub_service.length; i++) {

            if (service_id == this.details.sub_service[i].sid) {
                this.plans = this.details.sub_service[i].fetch_packages
                if (!this.plans) {
                    return
                }
                this.services.patchValue({
                    plan: this.plans[0].pid,
                    amount: this.plans[0].amount
                });
                this.selectedPlan = this.plans[i]
            }

        }

    }

    onPlanSelected(event) {

        let plan_id = event.target.value;

        for (let i = 0; i < this.plans.length; i++) {
            if (plan_id == this.plans[i].pid) {
                this.services.patchValue({
                    amount: this.plans[i].amount
                });

                this.selectedPlan = this.plans[i]
            }

        }

    }

    onChangePin(event) {

        let pin = event.target.value;

        this.currentPin = pin;

    }

    onSelectCoinType(type) {

        // this sets if the coin type is buy or sell
        this.coinServiceTypeSelected.type = type;

        this.onServiceTypeSelected = true;
    }

    onSubscribe() {
        this.subscribe_e_pin = true;
    }

    onUpgrade() {
        this.router.navigate(['/dashboard/settings'], {queryParams: {account_upgrade: 'active'}});
    }

    subscribeEPin() {
        this.subscribeLoader = true;
        this.userService.subscribeEPin().subscribe(
            (res: any) => {
                this.subscribeLoader = false;
                this.uiService.showToast('Successfully subscribed to E-PIN');
                this.ngOnInit();
            },
            (error) => {
                this.subscribeLoader = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );

    }

    purchaseEPin() {
        this.buyEPinLoader = true;
        this.userService.purchaseEPin(this.ePinQuery).subscribe(
            (res: any) => {
                this.buyEPinLoader = false;
                this.uiService.showToast(res.message);
                this.ePinQuery = {amount: '', network: '', purchase_count: '', type: ''};
                this.ngOnInit();
            },
            (error) => {
                this.buyEPinLoader = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );

    }

    confirmCoin() {

        if (this.coinQuery.amount == '') {
            this.uiService.showToast('Amount is required');
            return;
        }

        this.preview = !this.preview;
    }

    updatePrice(event) {

        if (this.subscription) {
            this.subscription.unsubscribe();
        }

        let amount = event.target.value;

        let type_check = (this.coinServiceTypeSelected.type == 'buy') ? this.dollarRateBuying : this.dollarRateSelling;

        this.nairaRate = amount * type_check;

        this.coinLoading = true;

        // convert to btc
        this.subscription = this.userService.fetchLiveTradeBTCtoUsd(amount).subscribe(
            (res: any) => {
                this.currentRate = res;
                this.coinLoading = false;
            },
            (error) => {
                this.coinLoading = false;
            }
        );
    }

    sellCoin() {
        this.coinQuery.transaction_pin = this.currentPin;
        this.purchaseLoading = true;
        this.userService.sellCoin(this.coinQuery).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.coinDetailSell = JSON.parse(res.data.token.coin_details);
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    buyCoin() {
        this.coinQuery.transaction_pin = this.currentPin;
        this.purchaseLoading = true;
        this.userService.buyCoin(this.coinQuery).subscribe(
            (res: any) => {
                this.purchaseLoading = false;
                this.uiService.showToast(res.message);
                this.coinQuery = {amount: '', transaction_pin: '', wallet_address: ''};
                this.ngOnInit();
            },
            (error) => {
                this.purchaseLoading = false;
                this.uiService.showToast((error.error) ? error.error.short_description || 'An error occurred' : 'Internet connection error');
            }
        );
    }

    cancelCoinProcess() {
        this.coinServiceTypeSelected.type = '';

        this.onServiceTypeSelected = false;

        this.currentRate = 0;

        this.nairaRate = 0;

        this.coinQuery = {amount: '', transaction_pin: '', wallet_address: ''};

        this.coinLoading = false;
    }

    copyBtcWalletAddress(walletId) {
        let inputValue = document.createElement('input');
        document.body.appendChild(inputValue);
        inputValue.value = walletId;
        inputValue.select();
        document.execCommand('copy', false);
        inputValue.remove();
        this.uiService.showToast('Address copied');
    }

    onChangeAirtimePaymentMethod(event) {

        this.selectAirtimeFundingMethod = event.target.value;

    }

    onSelectAirtimeService(service) {
        this.selectedAirtimeService = service;
    }

}
