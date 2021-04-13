import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListSettings, OGListStyleType, DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { fadeInAnimation } from 'src/app/core/route-animation/route.animation';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation],
  encapsulation: ViewEncapsulation.None
})
export class ListSurveyComponent implements OnInit, OnDestroy {

  path = 'survey';
  idScuola: string;
  idSpecializzando: string;
  isSegreteria: boolean;
  canSelectScuola: boolean;


  @ViewChild('surveyTable') surveyTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  @ViewChild('OGModalAnswer') OGModalAnswer: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  settings: OGListSettings;

  selectOptions = {
    statusList: Array<{ id: string, text: string }>(),
    tipi_utenti_survey: [],
    scuoleList: Array<{ id: string, text: string }>(),
  };
  dialogFields: Array<DialogFields> = [];

  selectOptionsAnswer = {
    statusList: Array<{ id: string, text: string }>()
  };
  dialogFieldsAnswer: Array<DialogFields> = [
  ];
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('SURVEY_LIST')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'titolo',
              name: res.TITOLO,
              style: OGListStyleType.BOLD
            },
            {
              column: 'data_inizio',
              name: res.DAL,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'data_fine',
              name: res.AL,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'status',
              name: res.STATO,
              style: OGListStyleType.NORMAL
            }
          ],
          customActions: [
            {
              icon: 'question_answer',
              name: res.DOMANDE,
              type: 'questions',
              condition: (element) => {
                return element.canEditDomande;
              }
            },

            {
              icon: 'check_circle_outline',
              name: res.RISPOSTE,
              type: 'answers',
              condition: (element) => {
                return element.canViewRisposte && element.idrisposta !== '';
              }
            },
            {
              icon: 'send',
              name: res.REPORT,
              type: 'analytics',
              condition: (element) => {
                return element.canRispondi && element.canViewRisposte && element.idrisposta !== '';
              }
            },
            {
              icon: 'phone',
              name: res.RISPONDI,
              type: 'answer',
              condition: (element) => {
                return (element.canRispondi && element.idrisposta === 0);
              }
            },
            {
              icon: 'format_list',
              name: res.RISPOSTE,
              type: 'answer_view',
              condition: (element) => {
                return (element.canViewRisposteSpecializzando && element.idrisposta > 0);
              }
            },
            {
              icon: 'leaderboard',
              name: res.REPORT,
              type: 'report',
              condition: (element) => {
                return (element.canViewReport === true ? true : false);
              }
            },
            {
              icon: 'edit',
              name: res.MODIFICA,
              type: 'edit',
              condition: (element) => {
                return (element.canModify);
              }
            },
            {
              icon: 'delete',
              name: res.ELIMINA,
              type: 'delete',
              condition: (element) => {
                return (element.canDelete);
              }
            },
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'titolo',
            pageSize: 20
          },
          actionColumns: {
            edit: false,
            delete: false,
          },
          search: '',
          selection: []
        };
        this.selectOptions.tipi_utenti_survey = [
          { id: 8, text: res.SPECIALIZZANDI },
          { id: 7, text: res.TUTOR }
        ];
        this.dialogFields = [
          {
            type: 'INPUT',
            placeholder: res.TITOLO,
            name: 'titolo'
          },
          {
            type: 'SELECT',
            placeholder: res.SCUOLA_DI_SPECIALIZZAZIONE,
            name: 'idscuola',
            selectOptions: 'scuoleList',
            selectMultiple: false,
            visible: () => {
              return this.canSelectScuola;
            }
          },
          {
            type: 'SELECT',
            placeholder: res.DESTINATARI_SURVEY,
            name: 'somministrata_a',
            selectOptions: 'tipi_utenti_survey',
            selectMultiple: true
          },
          {
            type: 'DATEPICKER',
            placeholder: res.DATA_INIZIO,
            name: 'data_inizio',
            col: '50'
          },
          {
            type: 'DATEPICKER',
            placeholder: res.DATA_FINE,
            name: 'data_fine',
            col: '50'
          },
          {
            type: 'SELECT',
            placeholder: res.STATO,
            name: 'idstatus_survey',
            selectOptions: 'statusList',
            col: '50'
          }
        ];
      });
  }

  ngOnInit() {
    this.pageTitleService.setTitle(this.translated.SURVEY, '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.settings.search = search;
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
    this.surveyTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}`,
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
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        this.isSegreteria = res.segreteria === 1 ? true : false;
        if (reset) {
          this.surveyTable.firstPage();
        }
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }

  operations(e) {
    switch (e.type) {
      case 'edit':
        this.edit(e.element.id);
        break;
      case 'delete':
        this.delete(e.element.id, e.element.limit_name);
        break;
      case 'questions':
        this.router.navigate([e.element.id], {relativeTo: this.aRoute});
        break;
      case 'answer':
        this.openSurvey(e.element.id);
        break;
      case 'answer_view':
        console.log( e.element.id );
        this.openSurveyView(e.element.id);
        break;
      case 'answers':
        this.router.navigate([e.element.id, 'risposte'], {relativeTo: this.aRoute});
        break;
      case 'report':
        this.router.navigate([e.element.id, 'report'], {relativeTo: this.aRoute});
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.canSelectScuola = res.canSelectScuola;
        this.dataModal(res)
          .subscribe((res2: any) => {
            this.setData(id, res2);
          });
      });
  }

  add(data = {}) {
    if (Object.entries(data).length > 0) {
      this.dataModal(data)
        .subscribe((res2) => {
          this.setData('0', res2, true);
        });
    } else {
      const obj: Rest = {
        type: 'GET',
        path: `${this.path}/0`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.canSelectScuola = res.canSelectScuola
          this.dataModal(res)
            .subscribe((res2) => {
              this.setData('0', res2, true);
            });
        });
    }
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_SURVEY, this.translated.ELIMINA_SURVEY_SUB + ' '
      + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${id}`
        };
        this.main.rest(obj)
          .then((res: any) => {
            this.getData();
          }, (err) => {
            this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.CHIUDI);
          });
      }, (err) => {
      });
  }

  dataModal(data: any): Observable<any> {
    if (data.status_list) {
      this.selectOptions.statusList = data.status_list;
    }
    if (data.scuole_list) {
      this.selectOptions.scuoleList = data.scuole_list;
    }

    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.SURVEY, '', data)
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

  setData(id: string, body: any, insert = false) {
    const obj: Rest = {
      type: insert ? 'PUT' : 'POST',
      path: `${this.path}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${id}`;
    }
    this.main.rest(obj)
      .then(() => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, 'Ok')
          .then(() => {
            if (insert) {
              this.add(body);
            } else {
              this.edit(id);
            }
          }, () => { });
      });
  }



  // START: Survey

  openSurvey(id: string, data = {}) {
    if (Object.entries(data).length > 0) {
      this.dataModalSurvey(data, false)
        .subscribe((res2) => {
          this.setDataSurvey(id, res2);
        });
    }
    const obj: Rest = {
      type: 'GET',
      path: `survey_risposte/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dataModalSurvey(res)
          .subscribe((res2: any) => {
            this.setDataSurvey(id, res2);
          });
      });
  }



  openSurveyView(id: string, data = {}) {
    if (Object.entries(data).length > 0) {
      this.dataModalSurvey(data, false)
        .subscribe((res2) => {
          this.setDataSurvey(id, res2);
        });
    }
    const obj: Rest = {
      type: 'GET',
      path: `survey_risposte/survey/${id}/0`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dataModalSurvey(res)
          .subscribe((res2: any) => {
            this.setDataSurvey(id, res2);
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
      this.OGModalAnswer.openModal(data.titolo, '', data.data)
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

  setDataSurvey(id: string, body: any) {
    const obj: Rest = {
      type: 'PUT',
      path: `survey_risposte/${id}`,
      body
    };
    this.main.rest(obj)
      .then(() => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.OK)
          .then(() => {
            this.openSurvey(id, { data: body });
          }, () => { });
      });
  }

  // END: Survey

}
