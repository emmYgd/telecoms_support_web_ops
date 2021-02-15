import {Component, EventEmitter, OnInit, Output} from '@angular/core';

@Component({
    selector: 'app-network-error',
    templateUrl: './network-error.component.html',
    styleUrls: ['./network-error.component.scss']
})
export class NetworkErrorComponent implements OnInit {

    @Output() onReloadEvent = new EventEmitter<string>();

    constructor() {
    }

    ngOnInit() {
    }

    onReload() {
        this.onReloadEvent.emit('true');
    }

}
