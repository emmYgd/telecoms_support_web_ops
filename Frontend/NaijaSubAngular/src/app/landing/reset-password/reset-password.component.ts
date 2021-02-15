import {Component, OnInit} from '@angular/core';
import {UiService} from "../../common/ui.service";
import {AuthService} from "../../auth/auth.service";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ActivatedRoute, Router} from "@angular/router";
import {environment} from "../../../environments/environment.prod";
import {HttpClient} from "@angular/common/http";

@Component({
    selector: 'app-reset-password',
    templateUrl: './reset-password.component.html',
    styleUrls: ['./reset-password.component.scss']
})
export class ResetPasswordComponent implements OnInit {

    loading = false;

    token;

    resetPassword: FormGroup;

    submitted = false;

    constructor(
        private authService: AuthService,
        private uiService: UiService,
        private formBuilder: FormBuilder,
        private activatedRoute: ActivatedRoute,
        private httpClient: HttpClient,
        private router: Router
    ) {
        this.token = activatedRoute.params['value'].token;
    }

    ngOnInit() {
        this.resetPasswordInit();
    }

    resetPasswordInit() {
        this.resetPassword = this.formBuilder.group({
            password: ['', [Validators.required]],
            confirm_password: ['', [Validators.required]],
            token: [this.token, [Validators.required]]
        });
    }

    get f() {
        return this.resetPassword.controls;
    }

    onResetPassword() {
        this.loading = true;
        this.submitted = true;
        if (this.resetPassword.invalid) {
            this.uiService.showToast('Password is required');
            this.loading = false;
            return;
        }

        this.httpClient.post(`${environment.api_url}auth/reset-password`, this.resetPassword.value).subscribe(
            (res: any) => {

                // redirect user to validation page
                this.loading = false;

                this.uiService.showToast('Password reset successful');

                this.router.navigate(['/login']);

            },
            (error) => {
                this.loading = false;
                this.uiService.showToast((error.error) ? error.error.short_description : 'Internet connection error');
            }
        );
    }

}
