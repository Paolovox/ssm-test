import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType, OGModalEvents } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-attivita-np-calendar',
  templateUrl: './attivita-np-calendar.component.html',
  styleUrls: ['./attivita-np-calendar.component.scss']
})
export class AttivitaNpCalendarComponent implements OnInit, OnDestroy {

  path = 'registrazioni_attivita_np_calendario';
  idAttivita: string;
  idScuola: string;

  @ViewChild('attivitaNpCalendarTable') attivitaNpCalendarTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'data_lezione_text',
        name: 'Data',
        style: OGListStyleType.BOLD
      },
      {
        column: 'insegnamento_text',
        name: 'Insegnamento',
        style: OGListStyleType.NORMAL
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'data_lezione',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  selectOptions = {
    coortiList: Array<{id: string, text: string}>(),
    anniList: Array<{ id: number, text: number }>(
      { id: 1, text: 1 },
      { id: 2, text: 2 },
      { id: 3, text: 3 },
      { id: 4, text: 4 },
      { id: 5, text: 5 }),
    settoriList: Array<{ id: string, text: string }>(),
    insegnamentiList: Array<{ id: string, text: string }>(),
  };

  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      placeholder: 'Coorte',
      name: 'idcoorte',
      selectOptions: 'coortiList',
      selectKeyText: 'nome',
      col: '50'
    },
    {
      type: 'SELECT',
      placeholder: 'Anno',
      name: 'anno_scuola',
      selectOptions: 'anniList',
      col: '50'
    },
    {
      type: 'SELECT',
      placeholder: 'Settore scientifico',
      name: 'idpds',
      selectOptions: 'settoriList',
      selectKeyText: 'nome',
      visible: (data) =>  {
        return data.anno_scuola && data.idcoorte;
      }
    },
    {
      type: 'SELECT',
      placeholder: 'Insegnamento',
      name: 'idinsegnamento',
      selectOptions: 'insegnamentiList',
      selectKeyText: 'nome',
      required: () => false,
      visible: (data) =>  {
        return data.idpds;
      }
    },
    {
      type: 'DATEPICKER',
      name: 'data_lezione',
      placeholder: 'Data lezione',
      col: '30'
    }
  ];

  tempCoorte = {
    anno_scuola: undefined,
    idcoorte: undefined
  };

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
    this.idAttivita = this.aRoute.snapshot.paramMap.get('idAttivita');
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle('Calendario lezioni', '');
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
    this.attivitaNpCalendarTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idAttivita}`,
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
          this.attivitaNpCalendarTable.firstPage();
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
        this.delete(e.element.id, e.element.insegnamento_text);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${this.idAttivita}/${id}`
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
        path: `${this.path}/${this.idScuola}/${this.idAttivita}/0`
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
    this.dialog.openConfirm('Elimina lezione', 'Sei sicuro di voler eliminare la lezione '
      + name + '?', 'ELIMINA', 'Annulla')
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
    if (data.coorti_list)  {
      this.selectOptions.coortiList = data.coorti_list;
      delete(data.coorti_list);
    }
    if (data.settori_scientifici_list)  {
      this.selectOptions.settoriList = data.settori_scientifici_list;
      delete (data.settori_scientifici_list);
    }
    if (data.insegnamenti_list)  {
      this.selectOptions.insegnamentiList = data.insegnamenti_list;
      delete (data.insegnamenti_list);
    }
    if (data.anno_scuola) {
      this.tempCoorte.anno_scuola = data.anno_scuola;
    }
    if (data.idcoorte) {
      this.tempCoorte.idcoorte = data.idcoorte;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda lezione', '', data)
        .subscribe((res) => {
          if (res.event === OGModalEvents.SELECTION_CHANGE) {
            this.getSettoriScientifici(res);
          }
          if (res.event === OGModalEvents.SELECTION_CHANGE && res.type === 'idpds') {
            this.getInsegnamenti(res.data.value);
          }
          if (res.event === 'confirm') {
            observer.next(res.data);
            observer.complete();
          }
        }, (err) => {
          observer.complete();
        });
    });
  }

  getSettoriScientifici(data: any) {
    this.tempCoorte[data.type] = data.data.value;
    if (!this.tempCoorte.anno_scuola || !this.tempCoorte.idcoorte) {
      return;
    }
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/settori_scientifici/${this.idScuola}/${this.tempCoorte.idcoorte}/${this.tempCoorte.anno_scuola}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.selectOptions.settoriList = res;
      }, (err) => {
    });
  }

  async getInsegnamenti(idpds: string) {
    const obj: Rest = {
      type: 'POST',
      path: `${this.path}/insegnamenti`,
      body: {
        idpds
      }
    };
    this.selectOptions.insegnamentiList = await this.main.rest(obj);
  }

  setData(id: string, body: any, insert = false) {
    const obj: Rest = {
      type: insert ? 'PUT' : 'POST',
      path: `${this.path}/${this.idScuola}/${this.idAttivita}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${this.idAttivita}/${id}`;
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
