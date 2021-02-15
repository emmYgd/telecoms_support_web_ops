import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {HttpClient} from "@angular/common/http";
import {environment} from "../../../environments/environment.prod";
import {UiService} from "../../common/ui.service";
import {AuthService} from "../../auth/auth.service";
import {Router} from "@angular/router";

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {

    submitted = false;

    loading = false;

    login: FormGroup;

    passwordType = 'password';

    constructor(
        private formBuilder: FormBuilder,
        private  httpClient: HttpClient,
        private uiService: UiService,
        private authService: AuthService,
        private router: Router
    ) {
    }

    ngOnInit() {

        this.loginInit();

    }

    loginInit() {
        this.login = this.formBuilder.group({
            email_username: ['', [Validators.required]],
            password: ['', [Validators.required]]
        });
    }

    get f() {
        return this.login.controls;
    }

    onLogin() {

        this.loading = true;

        this.submitted = true;

        if (this.login.invalid) {
            this.loading = false;
            return;
        }

        this.httpClient.post(`${environment.api_url}auth/login`, this.login.value).subscribe(
            (res: any) => {

                // redirect user to validation page

                if (!res.data.accessToken && res.message == 'success') {
                    this.authService.setUserObject(res.data.user);
                    this.router.navigate(['/2fa-auth']);
                    return;
                }

                this.authService.login(res.data.accessToken, res.data.staff_details);

                if (this.authService.isAuthenticate()) {

                    this.router.navigate(['/dashboard']);

                    this.authService.setUserObject(res.data.user);

                    this.uiService.showToast('Access granted');

                    return;
                }

                this.uiService.showToast('Authentication failed');
            },
            (error) => {
                this.loading = false;
                this.submitted = false;
                this.uiService.showToast((error.error) ? error.error.short_description : 'Internet connection error');
            }
        );

    }

    onPasswordUnHash() {
        if (this.passwordType == 'password') {
            this.passwordType = 'text';
        } else {
            this.passwordType = 'password'
        }
    }

}
