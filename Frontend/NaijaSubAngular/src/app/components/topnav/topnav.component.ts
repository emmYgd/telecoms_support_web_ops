import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../auth/auth.service";
import {Router} from "@angular/router";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-topnav',
    templateUrl: './topnav.component.html',
    styleUrls: ['./topnav.component.scss']
})
export class TopnavComponent implements OnInit {

    userDetails;

    name;

    isOpen = false;

    notificationCount = 0;

    notificationList: any;

    details;

    constructor(
        private authService: AuthService,
        private router: Router,
        private userService: UserService,
        private uiService: UiService
    ) {
    }

    ngOnInit() {
        this.userDetails = this.authService.getUserObject();

        let name = this.userDetails.name.split(' ');

        this.name = name[0];

        this.fetchNotification();

        this.fetchServices();
        setInterval(() => {
            this.fetchNotification();
        }, 3000)
    }

    toggleNavbar() {
        let elem = document.getElementById("sidebar-wrapper");
        let left = window.getComputedStyle(elem, null).getPropertyValue("left");
        if (left == "80px") {
            let styling = document.querySelector(".sidebar-toggle") as HTMLElement;
            styling.style.left = "-80px"
        } else if (left == "-80px") {
            let styling = document.querySelector(".sidebar-toggle") as HTMLElement;
            styling.style.left = "80px";
        }
    }

    onLogOut() {
        this.authService.logout();
        this.router.navigate(['/']);
    }

    fetchNotification() {
        this.userService.notification().subscribe(
            (res: any) => {
                this.notificationCount = res.notification_count;
                this.notificationList = res.notification_last_5;
            },
            (error) => {
            }
        );

    }

    fetchServices() {

        this.userService.fetchServices().subscribe(
            (res: any) => {

                this.details = res.data;
                console.log(this.details);
            },
            (error) => {
            }
        );

    }

    onViewNotification(i) {
        let data = this.notificationList[i];
        this.uiService.showToast(data.message);
        this.userService.readNotification({id: data.id}).subscribe(
            (response: any) => {
            },
            (error) => {
            }
        );
    }
}
