import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-attivita',
  templateUrl: './attivita.component.html',
  styleUrls: ['./attivita.component.scss']
})
export class AttivitaComponent implements OnInit, OnDestroy {

  path = 'registrazioni_attivita';
  idScuola: string;
  idCoorte: string;

  @ViewChild('attivitaTable') attivitaTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Nome attività',
        style: OGListStyleType.BOLD
      },
      {
        column: 'tipo_attivita_text',
        name: 'Tipo attività',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'tipo_registrazione_text',
        name: 'Tipo registrazione',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions: [
      {
        name: 'Schema attività',
        type: 'schema',
        icon: 'tune'
      },
      {
        name: 'Filtro attività',
        type: 'filtro',
        icon: 'filter_list'
      }
    ],
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

  selectOptions = {
    comboList: Array<{ id: string, text: string }>(),
    tipoAttivitaList: Array<{ id: string, text: string }>(),
    tipoRegistrazioniList: Array<{ id: string, text: string }>(),
    prestazioniList: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome attività',
      name: 'nome'
    },
    {
      type: 'SELECT',
      placeholder: 'Tipo attività',
      name: 'idtipo_attivita',
      selectOptions: 'tipoAttivitaList'
    },
    {
      type: 'SELECT',
      placeholder: 'Tipo registrazione',
      name: 'idtipo_registrazione',
      selectOptions: 'tipoRegistrazioniList'
    },
    {
      type: 'SELECT',
      placeholder: 'Prestazioni',
      name: 'prestazioni',
      selectOptions: 'prestazioniList',
      required: () => false,
      selectMultiple: true
    },
    {
      type: 'SELECT',
      placeholder: 'Combo',
      name: 'combo',
      selectOptions: 'comboList',
      required: () => false,
      selectMultiple: true
    },
    {
      type: 'SELECT',
      placeholder: 'Combo implicite',
      name: 'combo_implicite',
      selectOptions: 'comboList',
      required: () => false,
      selectMultiple: true
    },
    {
      type: 'CHECKBOX',
      placeholder: 'Note',
      name: 'opzione_note',
      required: () => false
    },
    {
      type: 'CHECKBOX',
      placeholder: 'Protocollo',
      name: 'opzione_protocollo',
      required: () => false
    },
    {
      type: 'CHECKBOX',
      placeholder: 'Upload file',
      name: 'opzione_upload',
      required: () => false
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
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.idCoorte = this.aRoute.snapshot.paramMap.get('idCoorte');
    this.pageTitleService.setTitle('Attività', '');
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
    this.attivitaTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idCoorte}`,
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
          this.attivitaTable.firstPage();
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
      case 'schema':
        this.router.navigate([e.element.id, 'schema'], { relativeTo: this.aRoute });
        break;
      case 'filtro':
        this.router.navigate([e.element.id, 'filtri'], { relativeTo: this.aRoute });
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`
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
        path: `${this.path}/${this.idScuola}/${this.idCoorte}/0`
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
    this.dialog.openConfirm('Elimina attività', 'Sei sicuro di voler eliminare l\'attività ' + name + '?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`
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
    if (data.attivita_tipologie_list) {
      this.selectOptions.tipoAttivitaList = data.attivita_tipologie_list;
    }
    if (data.registrazioni_tipi_list) {
      this.selectOptions.tipoRegistrazioniList = data.registrazioni_tipi_list;
    }
    if (data.combo_list) {
      this.selectOptions.comboList = data.combo_list;
    }
    if (data.prestazioni_list) {
      this.selectOptions.prestazioniList = data.prestazioni_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda attività', '', data)
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
      path: `${this.path}/${this.idScuola}/${this.idCoorte}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`;
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
