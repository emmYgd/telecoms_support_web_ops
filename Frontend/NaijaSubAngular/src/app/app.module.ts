import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {DashboardComponent} from './pages/dashboard/dashboard.component';
import {SidebarComponent} from './components/sidebar/sidebar.component';
import {FooterComponent} from './components/footer/footer.component';
import {TopnavComponent} from './components/topnav/topnav.component';
import {LandingComponent} from './landing/landing.component';
import {LoginComponent} from './landing/login/login.component';
import {ResetPasswordComponent} from './landing/reset-password/reset-password.component';
import {ForgotPasswordComponent} from './landing/forgot-password/forgot-password.component';
import {RegisterComponent} from './landing/register/register.component';
import {AuthService} from "./auth/auth.service";
import {UiService} from "./common/ui.service";
import {HTTP_INTERCEPTORS, HttpClientModule} from "@angular/common/http";
import {AuthHttpInterceptor} from "./auth/auth-http-interceptor";
import {MaterialModule} from "./material/material.module";
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {FormsModule, ReactiveFormsModule} from "@angular/forms";
import {VarificationComponent} from './landing/varification/varification.component';
import {TwoFaComponent} from './landing/two-fa/two-fa.component';
import {AccountComponent} from './pages/dashboard/account/account.component';
import {NetworkErrorComponent} from './common/network-error/network-error.component';
import {LoaderComponent} from './common/loader/loader.component';
import {FundingComponent} from './pages/funding/funding.component';
import {FundingDialogComponent} from './pages/funding/funding-dialog/funding-dialog.component';
import {FundingDetailsComponent} from './pages/funding/funding-details/funding-details.component';
import {CreateTransactionPinComponent} from './pages/create-transaction-pin/create-transaction-pin.component';
import {FundingHistoryComponent} from './pages/funding-history/funding-history.component';
import {FundingHistoryDescriptionComponent} from './pages/funding-history/funding-history-description/funding-history-description.component';
import {ServicesComponent} from './pages/services/services.component';
import {ServicesDetailsComponent} from './pages/services-details/services-details.component';
import {SidebarAuthComponent} from './components/sidebar-auth/sidebar-auth.component';
import {SettingsComponent} from './pages/settings/settings.component';
import {SavedCardsComponent} from './pages/settings/saved-cards/saved-cards.component';
import {MembershipLevelComponent} from './pages/settings/membership-level/membership-level.component';
import {WalletHistoryComponent} from './pages/settings/wallet-history/wallet-history.component';
import {WalletComponent} from './pages/wallet/wallet.component';
import {TransactionComponent} from './pages/transaction/transaction.component';
import { TransactionDetailsComponent } from './pages/transaction/transaction-details/transaction-details.component';
import { DownLineComponent } from './pages/down-line/down-line.component';
import { NotificationComponent } from './pages/notification/notification.component';

@NgModule({
    declarations: [
        AppComponent,
        DashboardComponent,
        SidebarComponent,
        FooterComponent,
        TopnavComponent,
        LandingComponent,
        LoginComponent,
        ResetPasswordComponent,
        ForgotPasswordComponent,
        RegisterComponent,
        VarificationComponent,
        TwoFaComponent,
        AccountComponent,
        NetworkErrorComponent,
        LoaderComponent,
        FundingComponent,
        FundingDialogComponent,
        FundingDetailsComponent,
        CreateTransactionPinComponent,
        FundingHistoryComponent,
        FundingHistoryDescriptionComponent,
        ServicesComponent,
        ServicesDetailsComponent,
        SidebarAuthComponent,
        SettingsComponent,
        SavedCardsComponent,
        MembershipLevelComponent,
        WalletHistoryComponent,
        WalletComponent,
        TransactionComponent,
        TransactionDetailsComponent,
        DownLineComponent,
        NotificationComponent
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        MaterialModule,
        BrowserAnimationsModule,
        HttpClientModule,
        FormsModule,
        ReactiveFormsModule
    ],
    providers: [
        AuthService,
        UiService,
        {
            provide: HTTP_INTERCEPTORS,
            useClass: AuthHttpInterceptor,
            multi: true
        }
    ],
    bootstrap: [AppComponent],
    entryComponents: [FundingDialogComponent, FundingHistoryDescriptionComponent]
})
export class AppModule {
}
