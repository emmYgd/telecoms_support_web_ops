import {Component, Inject, OnInit} from '@angular/core';
import {MatDialog, MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'app-funding-dialog',
    templateUrl: './funding-dialog.component.html',
    styleUrls: ['./funding-dialog.component.scss']
})
export class FundingDialogComponent implements OnInit {

    selected;

    details

    constructor(
        @Inject(MAT_DIALOG_DATA) private data: any,
        public dialogRef: MatDialogRef<FundingDialogComponent>,
    ) {
        this.selected = data.selected;
        this.details = data.details;
    }

    ngOnInit() {
    }

    onReload() {
        this.dialogRef.close({status: true});
    }


    onClose() {
        this.dialogRef.close({status: false});
    }
}
