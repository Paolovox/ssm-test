import { Component, OnInit } from '@angular/core';
import { MainUtilsService } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';

@Component({
  selector: 'app-scuola-dashboard',
  templateUrl: './scuola-dashboard.component.html',
  styleUrls: ['./scuola-dashboard.component.scss']
})
export class ScuolaDashboardComponent implements OnInit {

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService
  ) {
    this.pageTitleService.setTitle(this.main.getUserData('nomeScuola'), '');
  }

  ngOnInit() {
  }

}
