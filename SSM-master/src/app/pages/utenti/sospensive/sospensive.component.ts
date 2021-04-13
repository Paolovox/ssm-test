import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent,
  OGListSettings, OGListStyleType, OGListComponent, DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
  selector: 'app-sospensive',
  templateUrl: './sospensive.component.html',
  styleUrls: ['./sospensive.component.scss']
})
export class SospensiveComponent implements OnInit, OnDestroy {

  path = 'users';

  @ViewChild('sospensiveTable') sospensiveTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome_cognome',
        name: 'Nome',
        style: OGListStyleType.BOLD
      },
      {
        column: 'email',
        name: 'Email',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'codice_fiscale',
        name: 'Codice fiscale',
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
  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      placeholder: 'Tipologia',
      name: 'idtipo',
      selectOptions: 'tipiList'
    },
    {
      type: 'DATEPICKER',
      placeholder: 'Data inizio',
      name: 'data_inizio',
      col: '50'
    },
    {
      type: 'DATEPICKER',
      placeholder: 'Data fine',
      name: 'data_fine',
      col: '50'
    },
    {
      type: 'SELECT',
      placeholder: 'Anno',
      name: 'anno',
      selectOptions: 'anniList'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router
  ) {
  }

  ngOnInit() {
    this.pageTitleService.setTitle('Sospensive', '');
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
        this.delete(e.element.id, e.element.nome_cognome);
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
          this.dataModal(res)
            .subscribe((res2) => {
              this.setData('0', res2, true);
            });
        });
    }
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm('Elimina utente', 'Sei sicuro di voler eliminare la sospensiva ' + name + '?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${id}`
        };
        this.main.rest(obj)
          .then((res: any) => {
            this.getData();
          }, (err) => {
            this.dialog.openConfirm('Attenzione', err.error, 'Chiudi');
          });
      }, (err) => {
      });
  }

  dataModal(data: any): Observable<any> {
    if (data.tipi_list)  {
      this.selectOptions.tipiList = data.tipi_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Sospensiva utente', '', data)
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
        this.dialog.openConfirm('Attenzione', err.error, 'Ok')
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
