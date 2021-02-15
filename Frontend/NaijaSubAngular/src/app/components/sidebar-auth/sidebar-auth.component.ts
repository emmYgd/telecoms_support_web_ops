import {Component, OnInit} from '@angular/core';

@Component({
    selector: 'app-sidebar-auth',
    templateUrl: './sidebar-auth.component.html',
    styleUrls: ['./sidebar-auth.component.scss']
})
export class SidebarAuthComponent implements OnInit {
    isOpen = false;

    constructor() {
    }

    ngOnInit() {
    }

    sidebarToggle() {
        this.isOpen = !this.isOpen
    }


}
