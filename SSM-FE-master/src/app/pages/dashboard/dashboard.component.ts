import { Component, OnInit, OnDestroy, ViewEncapsulation, ViewChild } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import {
  MainUtilsService,
  Dialog,
  Rest} from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';
import { fadeInAnimation } from 'src/app/core/route-animation/route.animation';
import { Label } from 'ng2-charts';
import { BarChartComponent } from 'src/app/components/charts/bar-chart/bar-chart.component';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss'],
  encapsulation: ViewEncapsulation.None,
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation]
})
export class DashboardComponent implements OnInit, OnDestroy {

  path = 'dashboard';

  data: any;
  router$: Subscription;

  specializzandiNo: boolean;
  tutorNo: boolean;

  specializzandiNoRegistrazioni: any = {};
  tutorNoValutazioni = Array();
  idScuolaFilter = '';

  // bar chart label
  public numRegLabel: string[];

  // bar chart data
  public numRegData: any[] = [];

  // bar chart color
  public barColors: Array<any> = [
    {
      backgroundColor: '#1565c0',
      hoverBackgroundColor: '#6794dc'
    }
  ];

  public numRegPieData: number[];
  public numRegPieLabel: Label[];
  public pieColors = [
    {
      backgroundColor: ['rgba(255,0,0,1)', 'rgba(0,255,0,1)', 'rgba(0,0,255,1)', 'rgba(0,100,255,1)', 'rgba(100,0,150,1)'],
    },
  ];
  translated: any = {};


  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    public translate: TranslateService
  ) {
    this.translate.get('DASHBOARD')
      .subscribe((res: any) => {
        this.translated = res;
      });
  }

  ngOnInit() {
    this.getData();
    this.getDataGraph();
    this.pageTitleService.setTitle(this.translated.DASHBOARD, '');
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData();
      this.getDataGraph();
    });
  }

  ngOnDestroy() {
    this.router$.unsubscribe();
  }

  async getData() {
    const obj: Rest = {
      path: `${this.path}`,
      type: 'GET',
      queryParams: {
        idScuola: this.idScuolaFilter
      }
    };
    this.data = await this.main.rest(obj).catch(err => this.dialog.openConfirm(this.translated.ATTENZIONE, err, this.translated.OK));
  }

  async getDataGraph() {
    const obj: Rest = {
      path: `${this.path}/graph_reg`,
      type: 'GET',
      queryParams: {
        idScuola: this.idScuolaFilter
      }
    };
    const ret = await this.main.rest(obj).catch(err => this.dialog.openConfirm(this.translated.ATTENZIONE, err, 'Ok'));
    this.numRegLabel = ret.bar.labels;
    this.numRegData[0] = ret.bar.values;
    this.numRegPieLabel = ret.pie.labels;
    this.numRegPieData = ret.pie.values;
    setTimeout(() => {
      this.barColors = [
        {
          backgroundColor: '#1565c0',
          hoverBackgroundColor: '#6794dc'
        }
      ];
    }, 100);
  }

  async getSpecializzandiNo() {
    if (!this.specializzandiNo) {
      const obj: Rest = {
        path: `${this.path}/specializzandi_no`,
        type: 'GET',
        queryParams: {
          idScuola: this.idScuolaFilter
        }
      };
      this.specializzandiNoRegistrazioni = await this.main.rest(obj).catch(err => this.dialog.openConfirm(this.translated.ATTENZIONE, err, this.translated.OK));
      this.specializzandiNo = true;
    } else {
      this.specializzandiNo = false;
    }
  }

  async getTutorNo() {
    if (!this.tutorNo)  {
      const obj: Rest = {
        path: `${this.path}/tutor_no`,
        type: 'GET',
        queryParams: {
          idScuola: this.idScuolaFilter
        }
      };
      this.tutorNoValutazioni = await this.main.rest(obj).catch(err => this.dialog.openConfirm(this.translated.ATTENZIONE, err, this.translated.OK));
      this.tutorNo = true;
    } else {
      this.tutorNo = false;
    }
  }

  isAteneo() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole) {
      let idRoleNum = parseInt(idRole, 10);
      if (idRoleNum >= 2 && idRole <= 4) {
        return true;
      }
    }
    return false;
  }
}
