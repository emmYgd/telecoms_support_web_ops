import {Component, OnInit} from '@angular/core';
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-down-line',
    templateUrl: './down-line.component.html',
    styleUrls: ['./down-line.component.scss']
})
export class DownLineComponent implements OnInit {

    loading = false;

    network = false;

    success = false;

    details: any;

    view_in_direct_down_line = false;

    in_direct_down_line: any;

    in_direct_down_line_loader = false;

    activeSelectDownLine;

    constructor(
        private userService: UserService,
        private uiService: UiService
    ) {
    }

    ngOnInit() {
        this.loading = true;
        this.fetchDownLine();
    }

    fetchDownLine() {
        this.userService.fetchDownLIne().subscribe(
            (response: any) => {
                this.loading = false;
                this.success = true;
                this.details = response.data;
            },
            (error) => {
                this.loading = false;
                this.network = true;
            }
        );
    }

    onReload() {
        this.ngOnInit();
    }

    onViewInDirectDownLine(referral_id, downLine) {
        this.view_in_direct_down_line = true;

        this.in_direct_down_line_loader = true;

        this.activeSelectDownLine = downLine;

        this.userService.fetchInDirectDownLine({referral_id: referral_id}).subscribe(
            (response: any) => {
                this.in_direct_down_line_loader = false;
                this.in_direct_down_line = response.data.in_direct_down_line;
            },
            (error) => {
                this.in_direct_down_line_loader = false;
                this.uiService.showToast('An error occurred fetching down line');
            }
        );
    }

    onBack() {
        this.view_in_direct_down_line = !this.view_in_direct_down_line;
    }
}
