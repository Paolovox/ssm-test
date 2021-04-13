import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation, Inject } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListSettings, OGListStyleType,
  DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-studenti',
  templateUrl: './studenti.component.html',
  styleUrls: ['./studenti.component.scss']
})
export class ListStudentiSurveyComponent implements OnInit, OnDestroy {

  path = 'survey_risposte/specializzandi';
  idSurvey: string;

  @ViewChild('specializzandiTable') specializzandiTable: OGListComponent;
  @ViewChild('OGModalAnswer') OGModalAnswer: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  settings: OGListSettings;

  selectOptionsAnswer = {};
  dialogFieldsAnswer: Array<DialogFields> = [];
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('SURVEY_STUDENTI')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'nome_specializzando',
              name: res.NOME,
              style: OGListStyleType.BOLD
            },
            {
              column: 'data_risposta',
              name: res.DATA_RISPOSTA,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'ora_risposta',
              name: res.ORA_RISPOSTA,
              style: OGListStyleType.NORMAL
            },
          ],
          actionColumns: {
            edit: false,
            delete: false
          },
          customActions: [
            {
              icon: 'remove_red_eye',
              type: 'open',
              name: res.VISUALIZZA_RISPOSTE,
              condition: (element) => {
                return element.canViewRisposte;
              }
            },

          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'specializzando_nome',
            pageSize: 20
          },
          search: '',
          selection: []
        };
      });
  }

  ngOnInit() {
    //this.idSurvey = this.main.getUserData('idSurvey');
    this.idSurvey = this.aRoute.snapshot.paramMap.get('idSurvey');

    this.pageTitleService.setTitle(this.translated.STUDENTI, '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.settings.search = search;
        this.getData(true);
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData(true, true);
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
    this.specializzandiTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idSurvey}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.pageTitleService.setTitle(this.translated.SURVEY + ' ' + res.titolo, '');
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.specializzandiTable.firstPage();
        }
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }

  operations(e) {
    switch (e.type) {
      case 'open':
        this.openSurvey(e.element.id, e.element.iduser);
        break;
      default:
        break;
    }
  }

  // START: Survey

  openSurvey(id: string, iduser: string, data = {}) {
    const obj: Rest = {
      type: 'GET',
      path: `survey_risposte/survey/${this.idSurvey}/${iduser}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dataModalSurvey(res)
          .subscribe((res2: any) => {
            // this.setDataSurvey(id, res2);
          });
      });
  }

  dataModalSurvey(data: any, assign = true): Observable<any> {
    if (assign) {
      data.dialogFields.map(e => {
        if (e.required !== undefined && e.required === false) {
          e.required = () => false;
        } else {
          e.required = () => true;
        }
        if (e.readonly !== undefined && e.readonly === true) {
          e.readonly = () => true;
        } else {
          e.readonly = () => false;
        }
        return e;
      });
      this.dialogFieldsAnswer = data.dialogFields;
      this.selectOptionsAnswer = data.selectOptions;
    }
    console.log(data);
    return new Observable((observer) => {
      this.OGModalAnswer.openModal(this.translated.SURVEY, '', data.data, 'no-show', this.translated.CHIUDI)
        .subscribe((res: any) => {
          if (res.event === 'confirm') {
            observer.next(res.data);
            observer.complete();
          }
        }, (err) => {
          observer.complete();
        });
    });
  }

  // END: Survey

}
