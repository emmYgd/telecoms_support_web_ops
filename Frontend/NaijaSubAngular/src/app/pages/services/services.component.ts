import {ChangeDetectorRef, Component, OnInit} from '@angular/core';
import {environment} from "../../environments/environment.prod";
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-services',
    templateUrl: './services.component.html',
    styleUrls: ['./services.component.scss']
})
export class ServicesComponent implements OnInit {
    userDetails: any;

    loading = false;

    network = false;

    success = false;

    details;

    external_url = environment.external_api;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private cdf: ChangeDetectorRef
    ) {
        this.userDetails = authService.getUserObject();
    }

    ngOnInit() {
        this.loading = true;
        this.network = false
        this.success = false;
        this.fetchServices();
    }

    fetchServices(page = 1) {

        this.userService.fetchServices().subscribe(
            (res: any) => {

                this.details = res.data;

                this.success = true;

                this.loading = false;

                console.log(this.details);
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
