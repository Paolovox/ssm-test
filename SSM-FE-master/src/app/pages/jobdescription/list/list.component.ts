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
export class ListJobTabelleComponent implements OnInit, OnDestroy {

  path = 'jobtabelle';
  idScuola: string;
  idSpecializzando: string;
  isSegreteria: boolean;
  canSelectScuola: boolean;
  canAdd: boolean;

  @ViewChild('jobDescriptionTable') surveyTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  @ViewChild('OGModalJobTabelle') OGModalAnswer: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome_tabella',
        name: 'Nome',
        style: OGListStyleType.BOLD
      },
      {
        column: 'data_aggiornamento_text',
        name: 'Data aggiornamento',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions: [
      {
        icon: 'list_alt',
        name: 'Colonne',
        type: 'columns',
        condition: (element) => {
          return element.canEdit;
        }
      },
      {
        icon: 'menu_book',
        name: 'Visualizza',
        type: 'dati'
      },
      {
        icon: 'create',
        name: 'Modifica',
        type: 'edit',
        condition: (element) => {
          return element.canEdit;
        }
      },
      {
        icon: 'delete',
        name: 'Elimina',
        type: 'delete',
        condition: (element) => {
          return element.canEdit;
        }
      },
      /*
      {
        icon: 'phone',
        name: 'Rispondi',
        type: 'answer',
        condition: (element) => {
          return true;
        }
      },
      */

    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'norder',
      pageSize: 20
    },
    actionColumns: {
      edit: false,
      delete: false,
      drag: true
    },

    search: '',
    selection: []
  };

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
    this.translate.get('JOB_LIST')
      .subscribe((res: any) => {
        this.translated = res;
        this.selectOptions.tipi_utenti_survey = [
          { id: 8, text: res.SPECIALIZZANDI },
          { id: 7, text: res.TUTOR }
        ];
        this.dialogFields = [
          {
            type: 'INPUT',
            placeholder: res.NOME_TABELLA,
            name: 'nome_tabella'
          }
        ];
      });
  }

  ngOnInit() {
    this.pageTitleService.setTitle(this.translated.JOB_DESCRIPTION, '');
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
        this.canAdd = res.canAdd;
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
        this.delete(e.element.id, e.element.nome_tabella);
        break;
      case 'columns':
        this.router.navigate([e.element.id, 'colonne'], {relativeTo: this.aRoute});
        break;
      case 'drag':
        this.dragItem(e.element);
        break;
      case 'dati':
        this.router.navigate([e.element.id, 'dati'], {relativeTo: this.aRoute});
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

  dragItem(element: any) {
    const obj: Rest = {
       type: 'PUT',
       path: `${this.path}/order`,
       body: {
          cur_pos: element.previousIndex,
          des_pos: element.currentIndex,
          cur_id: element.data[element.previousIndex].id,
          des_id: element.data[element.currentIndex].id
       }
    };
    this.main.rest(obj)
      .then((res: any) => {
      }, (err) => {
    });
  }


  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_JOB, this.translated.ELIMINA_JOB_SUB + ' '
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
      this.ogModal.openModal('Survey', '', data)
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
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.OK)
          .then(() => {
            if (insert) {
              this.add(body);
            } else {
              this.edit(id);
            }
          }, () => { });
      });
  }


  // TODO: Buttare!

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
        this.dialog.openConfirm('Attenzione', err.error, 'Ok')
          .then(() => {
            this.openSurvey(id, { data: body });
          }, () => { });
      });
  }

  // END: Survey

}
