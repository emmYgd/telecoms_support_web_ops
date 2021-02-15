import {ChangeDetectorRef, Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {environment} from "../../../environments/environment";
import {ActivatedRoute, Router} from "@angular/router";

@Component({
    selector: 'app-settings',
    templateUrl: './settings.component.html',
    styleUrls: ['./settings.component.scss']
})
export class SettingsComponent implements OnInit {

    @ViewChild('closeVerifyBvn', {static: false}) closeVerifyBvn: ElementRef

    @ViewChild('closeUpdateProfile', {static: false}) closeUpdateProfile: ElementRef

    @ViewChild('closeUpdatePassword', {static: false}) closeUpdatePassword: ElementRef

    @ViewChild('closeUpdateBankInformation', {static: false}) closeUpdateBankInformation: ElementRef

    @ViewChild('closeMembershipLevel', {static: false}) closeMembershipLevel: ElementRef

    @ViewChild('closePin', {static: false}) closePin: ElementRef

    @ViewChild('updateMembershipLevelTap', {static: false}) updateMembershipLevelTap: ElementRef

    external_link = environment.external_api;

    userDetails;

    userCard;

    loading = false;

    network = false;

    success = false;

    profile: any;

    bvnQuery = {bvn: ''};

    verifyBvn = false;

    editProfile: FormGroup;

    onUpdateProfileLoader = false;

    updatePassword: FormGroup;

    updatePasswordLoader = false;

    bankInformation: FormGroup;

    bankInformationLoader = false;

    membershipLevel = {upgrade_membership_level: ''}

    membershipLevelLoader = false;

    updatePinQuery = {pin: '', old_pin: ''};

    updatePinLoader = false;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
        private formBuilder: FormBuilder,
        private cdf: ChangeDetectorRef,
        private activatedRouter: ActivatedRoute
    ) {
    }

    ngOnInit() {
        this.loading = true;
        this.userDetails = this.authService.getUserObject();
        this.editProfileInit();
        this.fetchProfile();
        this.updatePasswordInit();
        this.onBankInformationInit();

    }

    fetchProfile() {
        this.userService.profile({}).subscribe(
            (res: any) => {
                this.profile = res.data;
                this.success = true;
                this.loading = false;
                this.setCurrentLevel(this.userDetails.membership_level);

                this.editProfile.patchValue({
                    name: this.userDetails.name,
                    email: this.userDetails.email,
                    phone: this.userDetails.phone
                });

                this.activatedRouter.queryParams.subscribe(params => {
                    if (params.account_upgrade == 'active') {
                        this.updateMembershipLevelTap.nativeElement.click();

                    }
                });
            },
            (error) => {

                this.loading = false;

                this.network = false;

            }
        );
    }

    onReload() {
        this.ngOnInit();
    }

    onVerifyBvn() {
        this.verifyBvn = true;

        if (this.bvnQuery.bvn == '') {
            this.uiService.showToast('Bvn is required');
            return;
        }

        this.userService.bvnVerify(this.bvnQuery).subscribe(
            (res: any) => {

                this.uiService.showToast('Account has been verified');

                this.verifyBvn = false;

                this.bvnQuery = {bvn: ''};

                this.closeVerifyBvn.nativeElement.click();

                this.ngOnInit();

            },
            (error) => {
                this.verifyBvn = false;

                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );
    }

    editProfileInit() {
        this.editProfile = this.formBuilder.group({
            name: ['', [Validators.required]],
            email: ['', [Validators.required]],
            phone: ['', [Validators.required]],
            file: ['']
        });
    }

    imageUpload(event) {

        console.log(event)

        const file = (event.target as HTMLInputElement).files[0];

        this.editProfile.patchValue({
            file: file
        });

        const files = event.srcElement.files[0];

        const reader = new FileReader();

        reader.onloadend = () => {
            this.userDetails.avater = reader.result as string;
            this.cdf.detectChanges();
        };

        reader.readAsDataURL(files);

        this.cdf.detectChanges();
    }

    onUpdateProfile() {

        this.onUpdateProfileLoader = true;

        let data = this.editProfile.value;

        if (this.editProfile.invalid) {
            this.uiService.showToast('Kindly ensure all required fields are filled up');
            this.onUpdateProfileLoader = false;
            return;
        }

        this.userService.updateProfile(data).subscribe(
            (res: any) => {
                this.uiService.showToast('Account has been updated');

                this.onUpdateProfileLoader = false;

                this.authService.setUserObject(res.data.user);

                this.closeUpdateProfile.nativeElement.click();

                this.userDetails = this.authService.getUserObject();
            },
            (error) => {

                this.onUpdateProfileLoader = false;
                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );

    }

    updatePasswordInit() {
        this.updatePassword = this.formBuilder.group({
            old_password: ['', [Validators.required]],
            password: ['', [Validators.required]],
            confirm_password: ['', [Validators.required]]
        });
    }

    onUpdatePassword() {

        this.updatePasswordLoader = true;

        this.userService.updatePassword(this.updatePassword.value).subscribe(
            (res: any) => {
                this.uiService.showToast('Account password updated');

                this.updatePasswordLoader = false;

                this.closeUpdatePassword.nativeElement.click();
            },
            (error) => {

                this.updatePasswordLoader = false;
                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );
    }

    onBankInformationInit() {
        this.bankInformation = this.formBuilder.group({
            
            bank_name: ['', [Validators.required]],
            account_name: ['', [Validators.required]],
            account_number: ['', [Validators.required]],
            card_name:[''],
            card_number:[''],
            card_expiry_month: [''],
            card_expiry_year: [''],
            card_cvv: ['']

        });
    }

    onUpdateBankInformation() {

        this.bankInformationLoader = true;

        this.userService.updateBankInformation(this.bankInformation.value).subscribe(
            (res: any) => {
                this.uiService.showToast('Account information updated');

                this.bankInformationLoader = false;

                //this.authService.setUserObject(res.data.user);
                this.authService.setUserObject(res.data);

                let data = this.authService.getUserObject();

                this.userDetails = data.user;

                this.userCard = data.card

                this.closeUpdateBankInformation.nativeElement.click();
            },
            (error) => {

                this.bankInformationLoader = false;
                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );
    }

    setCurrentLevel(level) {
        console.log(this.profile.level)
        for (let i = 0; i < this.profile.level.length; i++) {
            if (this.profile.level[i].name.toLowerCase() == level.toLowerCase()) {
                this.membershipLevel.upgrade_membership_level = this.profile.level[i].id;
            }
        }
    }

    onUpgradeLevelMembership() {

        this.membershipLevelLoader = true;

        this.userService.upgradeMembershipLevel(this.membershipLevel).subscribe(
            (res: any) => {
                this.uiService.showToast('Account upgraded successfully');

                this.membershipLevelLoader = false;

                this.authService.setUserObject(res.data.user);

                this.userDetails = this.authService.getUserObject();

                this.closeMembershipLevel.nativeElement.click();
            },
            (error) => {

                this.membershipLevelLoader = false;
                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );
    }

    onUpdatePin() {

        if (this.updatePinQuery.old_pin == '') {
            this.uiService.showToast('Old pin is required');
            return;
        }

        if (this.updatePinQuery.pin == '') {
            this.uiService.showToast('Pin is required');
            return;
        }

        this.updatePinLoader = true;

        this.userService.updateTransactionPin(this.updatePinQuery).subscribe(
            (res: any) => {
                this.uiService.showToast('Transaction pin updated successfully');

                this.updatePinLoader = false;

                this.updatePinQuery = {pin: '', old_pin: ''};

                this.closePin.nativeElement.click();
            },
            (error) => {

                this.updatePinLoader = false;
                this.uiService.showToast((error.error) ? (error.error.short_description) ? error.error.short_description : 'An error occurred verifying service' : 'Internet connection error');
            }
        );
    }

}
