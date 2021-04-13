import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent,
  OGListSettings, OGListStyleType, OGListComponent, DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-sospensive',
  templateUrl: './sospensive.component.html',
  styleUrls: ['./sospensive.component.scss']
})
export class SospensiveComponent implements OnInit, OnDestroy {

  path = 'sospensive';

  @ViewChild('sospensiveTable') sospensiveTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings;

  selectOptions = {
    tipiList: Array<{ id: string, text: string }>(),
    anniList: Array<{ id: string, text: string }>(
      {id: '1', text: '1'},
      {id: '2', text: '2'},
      {id: '3', text: '3'},
      {id: '4', text: '4'},
      {id: '5', text: '5'},
      {id: '6', text: '6'},
    ),
  };
  dialogFields: Array<DialogFields> = [];
  idUtente: string;
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
    this.translate.get('SOSPENSIVE')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'tipo_sospensiva',
              name: res.TIPO_SOSPENSIVA,
              style: OGListStyleType.BOLD
            },
            {
              column: 'data_inizio',
              name: res.DATA_INIZIO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'data_fine',
              name: res.DATA_FINE,
              style: OGListStyleType.NORMAL
            }
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'data_inizio',
            pageSize: 20
          },
          search: '',
          selection: []
        };
        this.dialogFields = [
          {
            type: 'SELECT',
            placeholder: res.TIPOLOGIA,
            name: 'idtipo',
            selectOptions: 'tipiList'
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
            placeholder: res.ANNO,
            name: 'anno',
            selectOptions: 'anniList'
          }
        ];
      });
    this.idUtente = this.aRoute.snapshot.paramMap.get('idSpecializzando');
  }

  ngOnInit() {
    this.pageTitleService.setTitle(this.translated.SOSPENSIVE, '');
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
    this.sospensiveTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idUtente}`,
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
        if (reset) {
          this.sospensiveTable.firstPage();
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
        this.delete(e.element.id, e.element.tipo_sospensiva);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idUtente}/${id}`
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
        path: `${this.path}/${this.idUtente}/0`
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
    this.dialog.openConfirm(this.translated.ELIMINA_SOSPENSIVA, this.translated.ELIMINA_SOSPENSIVA_SUB + ' ' + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idUtente}/${id}`
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
    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.SOSPENSIVA_UTENTE, '', data)
        .subscribe((res: any) => {
          if (res.event === 'confirm')  {
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
      path: `${this.path}/${this.idUtente}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idUtente}/${id}`;
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
