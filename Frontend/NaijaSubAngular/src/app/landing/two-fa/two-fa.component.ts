import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {HttpClient} from "@angular/common/http";
import {Router} from "@angular/router";
import {AuthService} from "../../auth/auth.service";
import {environment} from "../../../environments/environment.prod";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-two-fa',
    templateUrl: './two-fa.component.html',
    styleUrls: ['./two-fa.component.scss']
})
export class TwoFaComponent implements OnInit {

    loading = false;

    twoFactor: FormGroup;

    constructor(
        private formBuilder: FormBuilder,
        private httpClient: HttpClient,
        private router: Router,
        private authService: AuthService,
        private uiService: UiService
    ) {
    }

    ngOnInit() {
        this.twoFactorInit();

        this.twoFactor.patchValue({
            uid: this.authService.getUserObject()['id']
        });
    }

    twoFactorInit() {
        this.twoFactor = this.formBuilder.group({
            code: ['', [Validators.required]],
            uid: ['', [Validators.required]]
        });
    }

    onValidate2Factor() {

        this.loading = true;

        if (this.twoFactor.invalid) {
            this.loading = false;
            return;
        }

        this.httpClient.post(`${environment.api_url}auth/two-factor-auth`, this.twoFactor.value).subscribe(
            (res: any) => {

                // redirect user to validation page
                this.authService.login(res.data.accessToken, res.data.staff_details);

                if (this.authService.isAuthenticate()) {

                    this.authService.setUserObject(res.data.user);

                    this.router.navigate(['/dashboard']);

                    this.uiService.showToast('Access granted');

                    return;
                }

                this.uiService.showToast('Authentication failed');
            },
            (error) => {
                this.loading = false;
                this.uiService.showToast((error.error) ? error.error.short_description : 'Internet connection error');
            }
        );

    }
}
