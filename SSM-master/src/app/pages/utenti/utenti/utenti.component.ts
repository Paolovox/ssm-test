import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent,
  OGListSettings, OGListStyleType, OGListComponent, DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
  selector: 'app-utenti',
  templateUrl: './utenti.component.html',
  styleUrls: ['./utenti.component.scss']
})
export class UtentiComponent implements OnInit, OnDestroy {

  path = 'users';

  @ViewChild('userTable') userTable: OGListComponent;
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
      },
      {
        column: 'anno_scuola',
        name: 'Anno',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'coorte_text',
        name: 'Coorte',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions:  [
      {
        name: 'Vedi utente',
        type: 'user',
        icon: 'person'
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome_cognome',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  selectOptions = {
    genere: Array<{ id: string, text: string }>(
      { id: 'M', text: 'Maschio' }, { id: 'F', text: 'Femmina' }, { id: 'NS', text: 'Non specificato' }
    ),
    ruoli_amministrativi_list: Array<{ id: string, text: string }>(),
    statiList: Array<{ id: string, text: string }>()
  };
  dialogFields = [
    {
      type: 'INPUT',
      placeholder: 'Nome',
      name: 'nome',
      col: '40'
    },
    {
      type: 'INPUT',
      placeholder: 'Cognome',
      name: 'cognome',
      col: '40'
    },
    {
      type: 'SELECT',
      selectOptions: 'genere',
      placeholder: 'Genere',
      name: 'genere',
      col: '20'
    },
    {
      type: 'INPUT',
      placeholder: 'Email',
      name: 'email',
      col: '50'
    },
    {
      type: 'INPUT',
      placeholder: 'Imposta password',
      name: 'password',
      col: '50',
      required: (rec) => {
        return !rec.id;
      }
    },
    {
      type: 'INPUT',
      placeholder: 'Telefono',
      name: 'telefono',
      col: '50'
    },
    {
      type: 'INPUT',
      placeholder: 'Codice fiscale',
      name: 'codice_fiscale',
      col: '50'
    },
    {
      type: 'DATEPICKER',
      placeholder: 'Data di nascita',
      required: () => false,
      name: 'data_nascita',
      col: '50'
    },
    {
      type: 'INPUT',
      placeholder: 'Luogo di nascita',
      required: () => false,
      name: 'luogo_nascita',
      col: '50'
    },
    {
      type: 'INPUT',
      placeholder: 'Indirizzo di residenza',
      required: () => false,
      name: 'residenza_indirizzo',
      col: '50'
    },
    {
      type: 'INPUT',
      placeholder: 'Città di residenza',
      required: () => false,
      name: 'residenza_citta',
      col: '20'
    },
    {
      type: 'INPUT',
      placeholder: 'Provincia di residenza',
      required: () => false,
      name: 'residenza_provincia',
      col: '20'
    },
    {
      type: 'INPUT',
      placeholder: 'Cap',
      required: () => false,
      name: 'residenza_cap',
      col: '10'
    },
    {
      type: 'TEXTAREA',
      placeholder: 'Note utente',
      required: () => false,
      name: 'note_utente'
    },
    {
      type: 'SELECT',
      selectOptions: 'statiList',
      placeholder: 'Stato',
      name: 'idstatus_specializzando',
      col: '50'
    },
    {
      type: 'DATEPICKER',
      placeholder: 'Data contratto',
      required: () => false,
      name: 'data_contratto',
      col: '50'
    },
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
    this.pageTitleService.setTitle('Utenti', '');
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
    this.userTable.clearSelection();
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
          this.userTable.firstPage();
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
      case 'user':
        this.router.navigate(['/utenti', e.element.id]);
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
    this.dialog.openConfirm('Elimina utente', 'Sei sicuro di voler eliminare l\'utente ' + name + '?', 'ELIMINA', 'Annulla')
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
    if (data.stati_list)  {
      this.selectOptions.statiList = data.stati_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda utente', '', data)
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
