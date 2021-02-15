import {ChangeDetectorRef, Component, OnInit} from '@angular/core';
import {environment} from "../../environments/environment.prod";
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {MediaMatcher} from "@angular/cdk/layout";
import {FundingDialogComponent} from "./funding-dialog/funding-dialog.component";

@Component({
    selector: 'app-funding',
    templateUrl: './funding.component.html',
    styleUrls: ['./funding.component.scss']
})
export class FundingComponent implements OnInit {

    mobileQuery: MediaQueryList;

    submit = false;

    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    external_url = environment.external_api;

    selected: any;

    mobile = false;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        public media: MediaMatcher,
        private cdf: ChangeDetectorRef
    ) {
        this.userDetails = authService.getUserObject();
        this.mobileQuery = media.matchMedia("(max-width: 600px)");
    }

    ngOnInit() {

        this.loading = true;

        this.fetchFundingMethods();
    }

    fetchFundingMethods() {

        this.userService.getFundingMethod().subscribe(
            (res: any) => {

                this.details = res.data;

                this.success = true;

                this.loading = false;

                if (!this.mobileQuery.matches) {
                    this.selected = this.details.method[0];
                } else {
                    this.mobile = true;
                }
            },
            (error) => {
                this.loading = false;
                this.network = true;
                this.uiService.showToast('Internet connection error.');
            }
        );

    }

    onSelectFunding(event) {
        this.selected = event;
        console.log(this.selected);
        if (this.mobile) {
            this.uiService.openDialogv3(FundingDialogComponent, this.cdf.detectChanges(), {
                selected: this.selected,
                details: this.details
            }, (r) => {
                if (r.status) {
                    this.ngOnInit();
                }
            });
        }
    }

    onReload() {
        this.ngOnInit();
    }

}
