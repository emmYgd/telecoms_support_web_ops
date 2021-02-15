import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../auth/auth.service";

@Component({
    selector: 'app-sidebar',
    templateUrl: './sidebar.component.html',
    styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

    userDetail: any;
    isOpen = false;

    constructor(
        private authService: AuthService
    ) {
        // this.userDetail = authService.getUserObject();
    }

    ngOnInit() {
    }

    sidebarToggle() {
        this.isOpen = !this.isOpen
    }

}
