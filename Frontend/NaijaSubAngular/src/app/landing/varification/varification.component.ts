import {Component, OnInit} from '@angular/core';
import {HttpClient} from "@angular/common/http";
import {ActivatedRoute, Router} from "@angular/router";
import {environment} from "../../environments/environment";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-varification',
    templateUrl: './varification.component.html',
    styleUrls: ['./varification.component.scss']
})
export class VarificationComponent implements OnInit {

    verifying = false;

    token;

    message = '';

    verified = false;

    constructor(
        private httpClient: HttpClient,
        private activatedRoute: ActivatedRoute,
        private uiService: UiService,
        private route: Router
    ) {
        this.token = activatedRoute.params['value'].token;
    }

    ngOnInit() {
        this.verify();
    }


    verify() {

        this.verifying = true;

        this.httpClient.post(`${environment.api_url}auth/verify-account`, {token: this.token}).subscribe(
            (res: any) => {

                this.uiService.showToast('Account verified successfully');

                this.route.navigate(['/login']);
                this.verified = true;
                this.uiService.showToast('Account verified successfully');
            },

            (error) => {

                this.verifying = false;

                this.message = 'Account verification failed';

                this.uiService.showToast('Account verification failed')
            }
        );
    }

}
