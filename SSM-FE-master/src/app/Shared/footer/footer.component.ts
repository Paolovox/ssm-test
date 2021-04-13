import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { MainUtilsService, Rest } from '@ottimis/angular-utils';

@Component({
    selector: 'app-footer',
    templateUrl: './footer.component.html',
    styleUrls: ['./footer.component.scss']
})
export class FooterComponent implements OnInit {

    helpdeskUrl: string;

    constructor(
        public router: Router,
        private main: MainUtilsService
    ) { }

    ngOnInit() {
        this.getHelpdesk();
    }

    onClick() {
        const first = location.pathname.split('/')[1];
        if (first === 'horizontal') {
            this.router.navigate(['/horizontal/dashboard/crm']);
        } else {
            this.router.navigate(['/dashboard/crm']);
        }
    }

    getHelpdesk() {
        const domain = window.location.hostname;
        const obj: Rest = {
            type: 'GET',
            path: `atenei/url/helpdesk`,
            queryParams: {
                domain
            }
        };
        this.main.rest(obj)
            .then((res: any) => {
                // Torna un oggetto
                this.helpdeskUrl = res.url;
            }, (err) => {
            });
    }
}
