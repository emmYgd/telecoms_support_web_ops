import {ChangeDetectorRef, Component, OnInit} from '@angular/core';
import {environment} from "../../environments/environment.prod";
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {Location} from "@angular/common";
import {FundingHistoryDescriptionComponent} from "./funding-history-description/funding-history-description.component";

@Component({
    selector: 'app-funding-history',
    templateUrl: './funding-history.component.html',
    styleUrls: ['./funding-history.component.scss']
})
export class FundingHistoryComponent implements OnInit {

    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    external_url = environment.external_api;

    counter: any;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private location: Location,
        private cdf: ChangeDetectorRef
    ) {
        this.userDetails = authService.getUserObject();
    }

    ngOnInit() {
        this.loading = true;
        this.network = false
        this.success = false;
        this.fetchFunding();
    }

    fetchFunding(page = 1) {

        this.userService.fetchFunding(page).subscribe(
            (res: any) => {

                this.details = res.data;

                this.success = true;

                this.loading = false;

                const count = (this.details.fund.last_page > 5) ? 5 : this.details.fund.last_page;

                this.counter = Array.from({length: count}, (v, k) => k + 1);

                console.log(this.details);
            },
            (error) => {
                this.loading = false;
                this.network = true;
                this.uiService.showToast('Internet connection error.');
            }
        );

    }

    next() {

        if (this.details.fund.current_page < this.details.fund.last_page) {

            this.fetchFunding(this.details.fund.current_page + 1);

        }

    }

    onBack() {
        this.location.back();
    }

    onReload() {
        this.ngOnInit();
    }

    onView(i) {
        this.uiService.openDialogv3(FundingHistoryDescriptionComponent, this.cdf.detectChanges(), {data: this.details.fund.data[i]}, (r) => {
            if (r.status) {
            }
        })
    }
}
