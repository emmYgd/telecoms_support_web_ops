import {Component, OnInit} from '@angular/core';
import {Router} from "@angular/router";
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-notification',
    templateUrl: './notification.component.html',
    styleUrls: ['./notification.component.scss']
})
export class NotificationComponent implements OnInit {
    loading = false;

    network = false;

    success = false;

    notificationToggle = false;

    notificationCount = 0;

    notification: any;

    page = 1;

    constructor(private router: Router, private auth: AuthService, private userService: UserService, private uiService: UiService) {
    }

    ngOnInit() {
        this.loading = true;
        this.fetchAllNotification();
    }

    fetchAllNotification() {
        this.loading = true;
        return this.userService.allNotification(this.page).subscribe(
            (response: any) => {
                console.log(response);
                this.notification = response.data.notification;
                this.success = true;
                this.loading = false;
            },
            (error) => {
                this.network = true;
                this.loading = false;
            }
        );
    }


    onView(id) {
        let data = this.notification[id];
        this.uiService.showToast(data.message);
        this.userService.readNotification({'id': data.id}).subscribe(
            (response: any) => {
            },
            (error) => {
            }
        );
    }

    onReload() {
        this.ngOnInit();
    }
}
