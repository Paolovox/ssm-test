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
  selector: 'app-domande',
  templateUrl: './domande.component.html',
  styleUrls: ['./domande.component.scss']
})
export class DomandeSurveyComponent implements OnInit, OnDestroy {

  path = 'survey_domande';
  idSurvey: string;

  @ViewChild('domandeSurveyTable') domandeSurveyTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings;

  selectOptions = {
    tipiList: Array<{id: string, text: string}>(),
    statusList: Array<{id: string, text: string}>(),
    risposteList: Array<{ id: string, text: string }>()
  };

  dialogFields: Array<DialogFields> = [];
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
    this.translate.get('SURVEY_DOMANDE')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'domanda',
              name: res.DOMANDA,
              style: OGListStyleType.BOLD
            },
            {
              column: 'tipo_risposta',
              name: res.TIPO_RISPOSTA,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'obbligatorio_text',
              name: res.OBBLIGATORIO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'status',
              name: res.STATO,
              style: OGListStyleType.NORMAL
            }
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'domanda',
            pageSize: 20
          },
          search: '',
          selection: []
        };
        this.dialogFields = [
          {
            type: 'INPUT',
            placeholder: res.DOMANDA,
            name: 'domanda'
          },
          {
            type: 'SELECT',
            placeholder: res.TIPO_RISPOSTA,
            name: 'idtipo_risposta',
            selectOptions: 'tipiList'
          },
          {
            type: 'CHIP_LIST',
            placeholder: res.RISPOSTE,
            name: 'risposte',
            completeOptions: '',
            helperText: res.INSERISCI_PREMI,
            visible: (o: any) => {
              return o.idtipo_risposta == 2 || o.idtipo_risposta == 3;
            }
          },
          {
            type: 'CHECKBOX',
            placeholder: res.OBBLIGATORIO,
            name: 'obbligatorio',
            required: () => false
          },
          {
            type: 'SELECT',
            placeholder: res.STATO,
            name: 'idstatus_domanda',
            selectOptions: 'statusList'
          }
        ]
      });
  }

  ngOnInit() {
    this.idSurvey = this.aRoute.snapshot.paramMap.get('idSurvey');
    this.pageTitleService.setTitle(this.translated.DOMANDE, '');
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
    this.domandeSurveyTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idSurvey}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.domandeSurveyTable.firstPage();
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
        this.delete(e.element.id, e.element.nome_campo);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idSurvey}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
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
        path: `${this.path}/${this.idSurvey}/0`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.dataModal(res)
            .subscribe((res2) => {
              this.setData('0', res2, true);
            });
        });
    }
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_DOMANDA, this.translated.ELIMINA_DOMANDA_SUB + ' '
      + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idSurvey}/${id}`
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
    if (data.tipi_list)  {
      this.selectOptions.tipiList = data.tipi_list;
    }
    if (data.status_list)  {
      this.selectOptions.statusList = data.status_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.DOMANDA, '', data)
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
      path: `${this.path}/${this.idSurvey}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idSurvey}/${id}`;
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

}
