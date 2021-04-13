import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-risposte-report',
  templateUrl: './risposte-report.component.html',
  styleUrls: ['./risposte-report.component.scss']
})
export class RisposteReportComponent implements OnInit, OnDestroy {

  path = 'survey/report';
  idSurvey: string;

  data: any;
  router$: Subscription;
  search$: Subscription;
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('SURVEY_REPORT')
      .subscribe((res: any) => {
        this.translated = res;
      });
  }

  ngOnInit() {
    this.idSurvey = this.aRoute.snapshot.paramMap.get('idSurvey');
    this.pageTitleService.setTitle(this.translated.TITOLO, '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.getData(true, false);
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData(true, false);
    });
  }

  ngOnDestroy() {
    this.searchService.clear();
    this.search$.unsubscribe();
    this.router$.unsubscribe();
  }

  getData(reset = false, loading = true) {
    if (loading) {
      this.main.loaderOn();
    }
    const obj: Rest = {
      path: `${this.path}/${this.idSurvey}`,
      type: 'GET'
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res;
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }
}
