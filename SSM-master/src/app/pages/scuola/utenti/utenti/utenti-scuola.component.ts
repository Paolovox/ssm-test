import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';

@Component({
  selector: 'app-utenti-scuola',
  templateUrl: './utenti-scuola.component.html',
  styleUrls: ['./utenti-scuola.component.scss']
})
export class UtentiScuolaComponent implements OnInit, OnDestroy {

  path = 'scuola_utenti';
  idScuola: string;

  @ViewChild('userTable') userTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  ruoliList: Array<any>;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome_utente',
        name: 'Nome',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'ruolo',
        name: 'Ruolo',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'coorte',
        name: 'Coorte',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'anno_scuola',
        name: 'Anno',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'email',
        name: 'Email',
        style: OGListStyleType.NORMAL
      },
    ],
    actionColumns: {
      edit: false,
      delete: false
    },
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome',
      pageSize: 20
    },
    search: '',
    selection: []
  };


  @ViewChild('OGModal') ogModal: OGModalComponent;
  selectOptions = {
    genere: Array<{ id: string, text: string }>(
      { id: 'M', text: 'Maschio' }, { id: 'F', text: 'Femmina' }, { id: 'NS', text: 'Non specificato' }
    ),
    ruoli_amministrativi_list: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome',
      name: 'nome'
    },
    {
      type: 'INPUT',
      placeholder: 'Cognome',
      name: 'cognome'
    },
    {
      type: 'SELECT',
      selectOptions: 'genere',
      placeholder: 'Genere',
      name: 'genere'
    },
    {
      type: 'INPUT',
      placeholder: 'Email',
      name: 'email'
    },
    {
      type: 'INPUT',
      placeholder: 'Telefono',
      name: 'telefono'
    },
    {
      type: 'INPUT',
      placeholder: 'Codice fiscale',
      name: 'codice_fiscale'
    },
    {
      type: 'INPUT',
      placeholder: 'Data di nascita',
      required: () => false,
      name: 'data_nascita'
    },
    {
      type: 'INPUT',
      placeholder: 'Luogo di nascita',
      required: () => false,
      name: 'luogo_nascita'
    },
    {
      type: 'INPUT',
      placeholder: 'Cap di residenza',
      required: () => false,
      name: 'residenza_cap'
    },
    {
      type: 'INPUT',
      placeholder: 'Città di residenza',
      required: () => false,
      name: 'residenza_citta'
    },
    {
      type: 'INPUT',
      placeholder: 'Indirizzo di residenza',
      required: () => false,
      name: 'residenza_indirizzo'
    },
    {
      type: 'INPUT',
      placeholder: 'Provincia di residenza',
      required: () => false,
      name: 'residenza_provincia'
    },
    {
      type: 'SELECT',
      selectOptions: 'ruoli_amministrativi_list',
      placeholder: 'Ruolo amministrativo',
      required: () => false,
      name: 'idruolo_amministrativo',
    },
    {
      type: 'SELECT',
      selectOptions: 'organi_list',
      placeholder: 'Membro di',
      required: () => false,
      name: 'idorgano'
    },
    {
      type: 'TEXTAREA',
      placeholder: 'Note utente',
      name: 'note_utente'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute
  ) {
    this.idScuola = this.aRoute.snapshot.paramMap.get('idScuola');
    this.path = `${this.path}/${this.idScuola}`;
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
        this.delete(e.element.id, e.element.name);
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
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda utente', '', data)
        .subscribe((res: any) => {
          this.ruoliList = data.ruoli_list;
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
