import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ActivatedRoute, Router} from "@angular/router";
import {HttpClient} from "@angular/common/http";
import {environment} from "../../../environments/environment.prod";
import {UiService} from "../../common/ui.service";
import {AuthService} from "../../auth/auth.service";

@Component({
    selector: 'app-register',
    templateUrl: './register.component.html',
    styleUrls: ['./register.component.scss']
})
export class RegisterComponent implements OnInit {

    register: FormGroup;

    loading = false;

    submitted = false;

    ref;

    constructor(
        private formBuilder: FormBuilder,
        private activatedRoute: ActivatedRoute,
        private httpClient: HttpClient,
        private uiService: UiService,
        private authService: AuthService,
        private router: Router
    ) {
        this.ref = (activatedRoute.params['value'].ref) ? activatedRoute.params['value'].ref : 'admin';
    }

    ngOnInit() {
        this.registerInit();
    }

    get f() {
        return this.register.controls;
    }

    registerInit() {
        this.register = this.formBuilder.group({

            first_name: ['', [Validators.required]],
            last_name: ['', [Validators.required]],
            email: ['', [Validators.required]],
            phone: ['', [Validators.required]],
            username:['', [Validators.required]],
            password: ['', [Validators.required]],
            confirm_password: ['', [Validators.required]],
            state_of_residence: [''],
            referral: [this.ref],
        
        });
    }

    onRegister() {

        this.loading = true;

        this.submitted = true;

        if (this.register.invalid) {
            this.loading = false;
            return;
        }

        this.httpClient.post(`${environment.api_url}auth/register`, this.register.value).subscribe(
            (res: any) => {

                // redirect user to validation page

                this.authService.setUserObject(res.data.user)

                this.router.navigate(['/login']);

                this.uiService.showToast('Account created successfully. Activation mail has been sent to your email address.');
            },
            (error) => {
                this.loading = false;
                this.submitted = false;
                this.uiService.showToast((error.error) ? error.error.short_description : 'Internet connection error');
            }
        );

    }

}
