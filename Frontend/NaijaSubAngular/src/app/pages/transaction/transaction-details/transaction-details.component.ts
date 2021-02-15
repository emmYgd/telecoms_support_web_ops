import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {UserService} from "../../../auth/user.service";
import {Location} from "@angular/common";

@Component({
    selector: 'app-transaction-details',
    templateUrl: './transaction-details.component.html',
    styleUrls: ['./transaction-details.component.scss']
})
export class TransactionDetailsComponent implements OnInit {

    loading = false;

    network = false;

    success = false;

    details: any;

    reference: any;

    info: any;

    constructor(
        private activatedRoute: ActivatedRoute,
        private userService: UserService,
        private location: Location
    ) {
        this.reference = activatedRoute.params['value'].reference;
    }

    ngOnInit() {
        this.loading = true;

        this.fetchDetails();
    }

    fetchDetails() {

        this.userService.fetchTransactionDetails({reference: this.reference}).subscribe(
            (res: any) => {

                this.details = res.data.transactions;

                this.info = res.data.details;

                this.success = true;

                this.loading = false;
            },
            (error) => {
                this.network = true;

                this.loading = false;
            }
        );

    }

    onReload() {
        this.ngOnInit();
    }

    onBack() {
        this.location.back();
    }

    onPinSplit(pin) {

        if (!pin) {
            return [];
        }

        let pin_split = pin.split(',');

        return pin_split;

    }
}
