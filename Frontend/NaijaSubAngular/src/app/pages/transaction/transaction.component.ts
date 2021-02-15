import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../auth/auth.service";
import {UserService} from "../../auth/user.service";
import {UiService} from "../../common/ui.service";

@Component({
    selector: 'app-transaction',
    templateUrl: './transaction.component.html',
    styleUrls: ['./transaction.component.scss']
})
export class TransactionComponent implements OnInit {

    loading = false;

    network = false;

    success = false;

    transactions: any;

    counter: any;

    page = 1;

    constructor(
        private authService: AuthService,
        private userService: UserService,
        private uiService: UiService,
    ) {
    }

    ngOnInit() {

        this.loading = true;

        this.fetchTransaction();

    }

    fetchTransaction() {
        this.userService.fetchTransaction(this.page).subscribe(
            (res: any) => {

                this.loading = false;

                this.transactions = res.data.transactions;

                this.success = true;

                if (this.transactions.last_page > 5) {
                    this.counter = Array.from({length: 5}, (v, k) => k + 1);
                } else {
                    this.counter = Array.from({length: this.transactions.last_page}, (v, k) => k + 1);
                }


            },
            (error) => {
                this.loading = false;
                this.network = true;
            }
        );
    }

    onReload() {
        this.ngOnInit();
    }

    onNext() {
        if (this.transactions.current_page < this.transactions.last_page) {
            this.page = this.transactions.current_page + 1;
            this.fetchTransaction();
        }
    }

    onPrevious() {
        if (this.transactions.current_page > 1) {
            this.page = this.transactions.current_page - 1;
            this.fetchTransaction();
        }
    }

    loadCurrent(count) {
        this.page = count;
        this.fetchTransaction();
    }

    filterTable() {
        let input, filter, table, tr, td, i, txtValue;
        input = document.getElementById('myInput');
        filter = input.value.toUpperCase();
        table = document.getElementById('kt_advance_table_widget_4');
        tr = table.getElementsByTagName('tr');
        for (i = 1; i < tr.length; i++) {
            let tds = tr[i].getElementsByTagName('td');
            let matches = false;
            for (let j = 0; j < tds.length; j++) {
                if (tds[j]) {
                    txtValue = tds[j].textContent || tds[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        matches = true;
                    }
                }
            }

            if (matches == true) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }

        }
    }
}
