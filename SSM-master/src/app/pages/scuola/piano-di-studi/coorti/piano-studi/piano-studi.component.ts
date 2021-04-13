import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, OGModalEvents, DialogResponse, OGListComponent,
  OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-piano-studi',
  templateUrl: './piano-studi.component.html',
  styleUrls: ['./piano-studi.component.scss']
})
export class PianoStudiComponent implements OnInit, OnDestroy {

  path = 'pds';

  @ViewChild('pdsTable') pdsTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;
  idScuola: string;
  idCoorte: string;
  idAmbito: string;

  settings: OGListSettings = {
    columns: [
      {
        column: 'ambito_text',
        name: 'Ambito disciplinare',
        style: OGListStyleType.BOLD
      },
      {
        column: 'nome_settore',
        name: 'Settore scientifico',
        style: OGListStyleType.BOLD
      },
      {
        column: 'anno',
        name: 'Anno',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'nome_tipologia',
        name: 'Tipologia',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'quantita',
        name: 'Quantità',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions: [
      {
        name: 'Insegnamenti',
        type: 'insegnamenti',
        icon: 'school'
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome_settore',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  @ViewChild('OGModal') ogModal: OGModalComponent;

  selectOptions = {
    settoriScientificiList: Array<any>(),
    anniList: Array<any>(),
    tipologie_attivita_list: Array<any>(),
    ambitiDisciplinariList: Array<any>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      selectOptions: 'ambitiDisciplinariList',
      placeholder: 'Ambito disciplinare',
      name: 'idambito_disciplinare'
    },
    {
      type: 'SELECT',
      selectOptions: 'settoriScientificiList',
      placeholder: 'Settore scientifico',
      name: 'idsettore_scientifico'
    },
    {
      type: 'SELECT',
      selectOptions: 'anniList',
      placeholder: 'Anno',
      name: 'anno'
    },
    {
      type: 'SELECT',
      selectOptions: 'tipologie_attivita_list',
      placeholder: 'Tipologia CFU',
      name: 'idtipologia_attivita',
      visible: () => false
    },
    {
      type: 'INPUT',
      inputType: 'number',
      placeholder: 'Quantità crediti',
      name: 'quantita'
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
    this.pageTitleService.setTitle(this.main.getUserData('nomeScuola'), '');
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.idCoorte = this.aRoute.snapshot.paramMap.get('idCoorte');
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
    this.pdsTable.clearSelection();
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
          this.pdsTable.firstPage();
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
        this.delete(e.element.id);
        break;
      case 'insegnamenti':
        this.router.navigate([e.element.id, 'insegnamenti'], {relativeTo: this.aRoute});
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

  delete(id: string) {
    this.dialog.openConfirm('Elimina attività', 'Sei sicuro di voler eliminare l\'attività?', 'ELIMINA', 'Annulla')
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
    if (data.ambiti_disciplinari_list)  {
      this.selectOptions.ambitiDisciplinariList = data.ambiti_disciplinari_list;
    }
    if (data.settori_scientifici_list)  {
      this.selectOptions.settoriScientificiList = data.settori_scientifici_list;
    }
    if (data.tipologie_attivita_list)  {
      this.selectOptions.tipologie_attivita_list = data.tipologie_attivita_list;
    }
    if (data.anni_list)  {
      this.selectOptions.anniList = data.anni_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda attività', '', data)
        .subscribe((res: DialogResponse) => {
          if (res.event === OGModalEvents.SELECTION_CHANGE && res.type === 'idambito_disciplinare')  {
            this.getSettoriScientifici(res.data);
          }
          if (res.event === OGModalEvents.SELECTION_CHANGE && res.type === 'idsettore_scientifico') {
            this.getTipologieCFU(res.data);
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

  getSettoriScientifici(data) {
    this.selectOptions.settoriScientificiList = Array<any>();
    this.idAmbito = data.value;
    const obj: Rest = {
      type: 'GET',
      path: `pds/settori_scientifici/${this.idScuola}/${this.idCoorte}/${data.value}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.selectOptions.settoriScientificiList = res.settori_scientifici_list;
      }, (err) => {
    });
  }

  getTipologieCFU(data)  {
    this.selectOptions.tipologie_attivita_list = Array<any>();
    const obj: Rest = {
      type: 'GET',
      path: `pds/tipologia_cfu/${this.idScuola}/${this.idCoorte}/${this.idAmbito}/${data.value}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (res.length > 0) {
          this.dialogFields.map(e => {
            if (e.type === 'SELECT' && e.name === 'idtipologia_attivita')  {
              e.visible = () => true;
            }
            return e;
          });
          this.selectOptions.tipologie_attivita_list = res;
        }
      }, (err) => {
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
