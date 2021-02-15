import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {Location} from "@angular/common";

@Component({
    selector: 'app-create-transaction-pin',
    templateUrl: './create-transaction-pin.component.html',
    styleUrls: ['./create-transaction-pin.component.scss']
})
export class CreateTransactionPinComponent implements OnInit {

    submit = false;

    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    createPin: FormGroup;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private formBuilder: FormBuilder,
        private location: Location
    ) {
        this.userDetails = authService.getUserObject();
    }

    ngOnInit() {

        this.loading = true;

        this.fetchFundingMethods();

        this.createPinInit();
    }


    fetchFundingMethods() {

        this.userService.getFundingMethod().subscribe(
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

    createPinInit() {
        this.createPin = this.formBuilder.group({
            pin: ['', [Validators.required]],
            confirm_pin: ['', [Validators.required]]
        });
    }

    onCreatePin() {

        if (this.createPin.get('confirm_pin').value !== this.createPin.get('pin').value) {
            this.uiService.showToast('Confirm pin provided does not match match provided pin');
            return;
        }

        if (this.createPin.invalid) {
            this.uiService.showToast('4 digit Pin is required ');
            return;
        }

        this.submit = true;

        this.userService.createPin(this.createPin.value).subscribe(
            (res: any) => {

                this.uiService.showToast('Pin created successfully.');

                this.location.back();

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
}
