import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {DashboardComponent} from './pages/dashboard/dashboard.component';
import {LoginComponent} from "./landing/login/login.component";
import {RegisterComponent} from "./landing/register/register.component";
import {ForgotPasswordComponent} from "./landing/forgot-password/forgot-password.component";
import {ResetPasswordComponent} from "./landing/reset-password/reset-password.component";
import {VarificationComponent} from "./landing/varification/varification.component";
import {TwoFaComponent} from "./landing/two-fa/two-fa.component";
import {AuthGuard} from "./auth/auth-guard.services";
import {AccountComponent} from "./pages/dashboard/account/account.component";
import {FundingComponent} from "./pages/funding/funding.component";
import {CreateTransactionPinComponent} from "./pages/create-transaction-pin/create-transaction-pin.component";
import {FundingHistoryComponent} from "./pages/funding-history/funding-history.component";
import {ServicesComponent} from "./pages/services/services.component";
import {ServicesDetailsComponent} from "./pages/services-details/services-details.component";
import {SettingsComponent} from "./pages/settings/settings.component";
import {WalletComponent} from "./pages/wallet/wallet.component";
import {TransactionComponent} from "./pages/transaction/transaction.component";
import {TransactionDetailsComponent} from "./pages/transaction/transaction-details/transaction-details.component";
import {DownLineComponent} from "./pages/down-line/down-line.component";
import {NotificationComponent} from "./pages/notification/notification.component";


const routes: Routes = [
    {path: '', component: LoginComponent},
    {path: 'login', component: LoginComponent},
    {path: 'register', component: RegisterComponent},
    {path: '2fa-auth', component: TwoFaComponent},
    {path: 'verification/:token', component: VarificationComponent},
    {path: 'register/:ref', component: RegisterComponent},
    {path: 'forgot-password', component: ForgotPasswordComponent},
    {path: 'reset-password/:token', component: ResetPasswordComponent},
    {
        path: 'dashboard', component: DashboardComponent, children: [
            {path: '', redirectTo: 'account', pathMatch: 'full'},
            {path: 'account', component: AccountComponent},
            {path: 'funding', component: FundingComponent},
            {path: 'funding-history', component: FundingHistoryComponent},
            {path: 'services', component: ServicesComponent},
            {path: 'purchase/:id', component: ServicesDetailsComponent},
            {path: 'create-transaction-pin', component: CreateTransactionPinComponent},
            {path: 'settings', component: SettingsComponent},
            {path: 'wallet', component: WalletComponent},
            {path: 'transaction', component: TransactionComponent},
            {path: 'transaction-details/:reference', component: TransactionDetailsComponent},
            {path: 'down-line', component: DownLineComponent},
            {path: 'notification', component: NotificationComponent},
        ], canActivate: [AuthGuard]
    },
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
