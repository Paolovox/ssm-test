import { Component, OnInit, ViewChild, OnDestroy, ViewEncapsulation } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent,
  OGListSettings, OGListComponent, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { ActivatedRoute, Router, RouterEvent, NavigationEnd } from '@angular/router';
import { MenuItems, MenuTypes } from 'src/app/core/menu/menu-items/menu-items';
@Component({
  selector: 'app-scuole-di-specializzazione',
  templateUrl: './scuole-di-specializzazione.component.html',
  styleUrls: ['./scuole-di-specializzazione.component.scss'],
  encapsulation: ViewEncapsulation.None
})
export class ScuoleDiSpecializzazioneComponent implements OnInit, OnDestroy {

  path = 'scuole_di_specializzazione';

  @ViewChild('scuoleTable') scuoleTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  nomeAteneo: string;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome_scuola',
        name: 'Nome scuola',
        style: OGListStyleType.BOLD_LINK,
        eventType: 'selectScuola'
      },
      {
        column: 'telefono',
        name: 'Telefono',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'email',
        name: 'Mail',
        style: OGListStyleType.NORMAL
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome_scuola',
      pageSize: 20
    },
    search: '',
    selection: []
  };
  idScuola: string;

  scuoleDiSpecializzazione: Array<any>;
  idAteneo: string;

  @ViewChild('OGModal') ogModal: OGModalComponent;
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Mail',
      required: () => false,
      name: 'email'
    },
    {
      type: 'INPUT',
      placeholder: 'Telefono',
      required: () => false,
      name: 'telefono'
    },
    {
      type: 'INPUT',
      placeholder: 'Indirizzo',
      required: () => false,
      name: 'indirizzo'
    },
    {
      type: 'INPUT',
      placeholder: 'Comune',
      required: () => false,
      name: 'comune'
    },
    {
      type: 'INPUT',
      placeholder: 'Provincia',
      required: () => false,
      name: 'provincia'
    },
    {
      type: 'INPUT',
      placeholder: 'CAP',
      required: () => false,
      name: 'cap'
    },
    {
      type: 'INPUT',
      placeholder: 'Url',
      required: () => false,
      name: 'url'
    },
    {
      type: 'TEXTAREA',
      placeholder: 'Finalità scuola',
      required: () => false,
      name: 'finalita_scuola'
    },
    {
      type: 'CHECKBOX',
      placeholder: 'Manutenzione',
      required: () => false,
      name: 'manutenzione_status'
    },
    {
      type: 'TEXTAREA',
      visible: () => true,
      placeholder: 'Messaggio manutenzione',
      required: () => false,
      name: 'manutenzione_messaggio'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private aRoute: ActivatedRoute,
    private router: Router,
    private menuItem: MenuItems
  ) {
  }

  ngOnInit() {
    this.pageTitleService.setTitle('Scuole di specializzazione', '');
    this.idAteneo = this.aRoute.snapshot.paramMap.get('idAteneo');
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
    this.scuoleTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idAteneo}`,
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
        this.nomeAteneo = res.rows[0].nome_ateneo;
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.scuoleTable.firstPage();
        }
      }, () => {
      });
  }

  operations(e) {
    switch (e.type) {
      case 'edit':
        this.edit(e.element.id);
        break;
      case 'delete':
        this.delete(e.element.id, e.element.nome_scuola);
        break;
      case 'selectScuola':
        this.selectSchool(e.element.id, e.element.nome_scuola, e.element.nome_ateneo);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${id}/${this.idAteneo}`
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
        path: `${this.path}/0/${this.idAteneo}`
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
    this.dialog.openConfirm('Elimina scuola', 'Sei sicuro di voler eliminare la scuola ' + name + '?', 'ELIMINA', 'Annulla')
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
      this.ogModal.openModal(data.nome_scuola, '', data)
        .subscribe((res: any) => {
          if (res.event === 'confirm') {
            observer.next(res.data);
            observer.complete();
          } else if (res.event === 'checkboxChange' && res.type === 'manutenzione')  {
            // obj[9].visible = !obj[9].visible;
          }
        }, (err) => {
          observer.complete();
        });
    });
  }

  selectSchool(id: string, nomeScuola: string, nomeAteneo: string)  {
    const obj = {
      nomeAteneo,
      nomeScuola,
      idScuola: id,
      idAteneo: this.idAteneo
    };
    this.main.setUserData(obj);
    this.menuItem.switchMenu(MenuTypes.SCUOLE);
    this.router.navigate([id, 'dashboard']);
  }

  setData(id: string, body: any, insert = false) {
    const obj: Rest = {
      type: insert ? 'PUT' : 'POST',
      path: `${this.path}/${this.idAteneo}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${id}/${this.idAteneo}`;
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
