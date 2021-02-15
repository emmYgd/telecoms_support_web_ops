import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {AuthService} from "../../auth/auth.service";
import {HttpClient} from "@angular/common/http";
import {environment} from "../../../environments/environment.prod";
import {UiService} from "../../common/ui.service";
import {Router} from "@angular/router";

@Component({
    selector: 'app-forgot-password',
    templateUrl: './forgot-password.component.html',
    styleUrls: ['./forgot-password.component.scss']
})
export class ForgotPasswordComponent implements OnInit {

    forgotPassword: FormGroup;

    loading = false;

    submitted = false;

    constructor(
        private formBuilder: FormBuilder,
        private authService: AuthService,
        private httpClient: HttpClient,
        private uiService: UiService,
        private router: Router
    ) {
    }

    ngOnInit() {
        this.forgotPasswordInit();
    }

    forgotPasswordInit() {
        this.forgotPassword = this.formBuilder.group({
            email: ['', [Validators.required]]
        });
    }

    get f() {
        return this.forgotPassword.controls;
    }

    onForgotPassword() {
        this.loading = true;

        this.submitted = true;

        if (this.forgotPassword.invalid) {
            this.uiService.showToast('Email address is required');
            this.loading = false;
            return;
        }

        this.httpClient.post(`${environment.api_url}auth/forgot-password`, this.forgotPassword.value).subscribe(
            (res: any) => {

                // redirect user to validation page
                this.loading = false;

                this.uiService.showToast('Reset mail password sent to the email address provided.');
            },
            (error) => {
                this.loading = false;
                this.uiService.showToast((error.error) ? error.error.short_description : 'Internet connection error');
            }
        );
    }

}
