import {Component, Inject, OnInit} from '@angular/core';
import {MatDialog, MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'app-funding-history-description',
    templateUrl: './funding-history-description.component.html',
    styleUrls: ['./funding-history-description.component.scss']
})
export class FundingHistoryDescriptionComponent implements OnInit {

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<FundingHistoryDescriptionComponent>,
    ) {
    }

    ngOnInit() {

    }


    onClose() {
        this.dialogRef.close({status: false});
    }

    transactionType(type) {
        if (!type) {
            return 'Funding';
        }
        let transaction = type.split('_');

        if (transaction[0] == 'manuel') {
            return 'Manuel Funding';
        }

        if (transaction[0] == 'gateway') {
            return 'FlutterWave Funding';
        }

        if (transaction[0] == 'bank') {
            return 'Providus Funding';
        }

        if (transaction[0] == 'bank') {
            return 'Internal Funding (transfer)';
        }

        return (transaction[1]) ? transaction[0] + ' ' + transaction[1] : transaction[0];
    }
}
