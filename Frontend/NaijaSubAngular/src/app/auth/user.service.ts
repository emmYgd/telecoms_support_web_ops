import {Injectable} from '@angular/core';
import {environment} from "../../environments/environment";
import {HttpClient} from "@angular/common/http";

@Injectable({
    providedIn: 'root'
})
export class UserService {


    url = environment.api_url + 'v1/';

    web_hook_url = environment.api_url + 'web-hook/';

    constructor(private httpClient: HttpClient) {
    }

    getFundingMethod() {
        return this.httpClient.post(`${this.url}fund-methods`, {});
    }

    getDashboard() {
        return this.httpClient.post(`${this.url}fetch-dashboard`, {});
    }

    createPin(data) {
        return this.httpClient.post(`${this.url}create-transaction-pin`, data);
    }

    fundWallet(data) {
        return this.httpClient.post(`${this.url}fund-wallet`, data);
    }

    fetchFunding(page) {
        return this.httpClient.post(`${this.url}fetch-funding?page=${page}`, {});
    }

    fetchServices() {
        return this.httpClient.post(`${this.url}fetch-services`, {});
    }

    fetchSubServices(data) {
        return this.httpClient.post(`${this.url}fetch-sub-services`, data);
    }

    initTransaction(data) {
        return this.httpClient.post(`${this.url}init-transaction`, data);
    }

    airtimePurchase(data) {
        return this.httpClient.post(`${this.url}purchase-airtime`, data);
    }

    dataPurchase(data) {
        return this.httpClient.post(`${this.url}purchase-data`, data);
    }

    internetPurchase(data) {
        return this.httpClient.post(`${this.url}purchaseInternet`, data);
    }

    cablePurchase(data) {
        return this.httpClient.post(`${this.url}purchase-cable-service`, data);
    }

    electricityPurchase(data) {
        return this.httpClient.post(`${this.url}purchase-electricity-service`, data);
    }

    profile(data) {
        return this.httpClient.post(`${this.url}profile`, data);
    }

    bvnVerify(data) {
        return this.httpClient.post(`${this.url}verify-bvn`, data);
    }

    updatePassword(data) {
        return this.httpClient.post(`${this.url}update-password`, data);
    }

    updateBankInformation(data) {
        return this.httpClient.post(`${this.url}update-bank-information`, data);
    }

    updateProfile(data) {
        let formData = new FormData();
        formData.append('name', data.name);
        formData.append('email', data.email);
        formData.append('phone', data.phone);
        formData.append('file', data.file);
        return this.httpClient.post(`${this.url}update-profile`, formData);
    }

    upgradeMembershipLevel(data) {
        return this.httpClient.post(`${this.url}upgrade-membership`, data);
    }

    updateTransactionPin(data) {
        return this.httpClient.put(`${this.url}update-transaction-pin`, data);
    }

    withdraw(data) {
        return this.httpClient.post(`${this.url}withdraw`, data);
    }

    makeWithdraw(data) {
        return this.httpClient.post(`${this.url}make-withdraw`, data);
    }

    fetchTransaction(page) {
        return this.httpClient.post(`${this.url}transactions?page=${page}`, {});
    }

    fetchTransactionDetails(data) {
        return this.httpClient.post(`${this.url}transactions-details`, data);
    }

    subscribeEPin() {
        return this.httpClient.post(`${this.url}subscribe-epin`, {});
    }

    purchaseEPin(data) {
        return this.httpClient.post(`${this.url}purchase-e-pin`, data);
    }

    verifyWalletUser(data) {
        return this.httpClient.post(`${this.url}verify-wallet-user`, data);
    }

    sellCoin(data) {
        return this.httpClient.post(`${this.url}sell-coin`, data);
    }

    buyCoin(data) {
        return this.httpClient.post(`${this.url}buy-coin`, data);
    }

    fetchLiveTradeBTCtoUsd(amount) {
        return this.httpClient.post(`${this.web_hook_url}live-btc-conversion`, {amount: amount});
    }

    notification() {
        return this.httpClient.post(`${this.url}notification`, {});
    }

    sellAirtime(data) {
        return this.httpClient.post(`${this.url}sell-airtime`, data);
    }

    fetchDownLIne() {
        return this.httpClient.post(`${this.url}down-line`, {});
    }

    fetchInDirectDownLine(data) {
        return this.httpClient.post(`${this.url}in-direct-down-line`, data);
    }

    readNotification(data) {
        return this.httpClient.post(`${this.url}read-notification`, data);
    }

    allNotification(page) {
        return this.httpClient.post(`${this.url}all-notification?page=${page}`, {});
    }
}
