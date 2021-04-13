import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation, Inject } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListSettings, OGListStyleType,
  DialogFields } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-list-studenti',
  templateUrl: './list-studenti.component.html',
  styleUrls: ['./list-studenti.component.scss']
})
export class ListStudentiComponent implements OnInit, OnDestroy {

  path = 'specializzando_valutazioni';
  idScuola: string;

  @ViewChild('specializzandiTable') specializzandiTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  @ViewChild('OGModalTurni') ogModalTurni: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  oreValutate: any;

  idstato = '';
  dataAttivita = '';
  idtutor = '';
  idattivita = '';
  trainerTutor = 0;

  confermaStato: string;

  statusList: Array<any>;
  tutorList: Array<any>;
  attivitaList: Array<any>;

  contatori: Array<any>;
  idTurno: string;
  idScuolaFilter = '';

  scuole: any = [];

  settings: OGListSettings;

  selectOptions = {
    voti: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [];

  selectOptionsTurni = {
    turni: Array<{ id: string, text: string }>()
  };
  dialogFieldsTurni: Array<DialogFields> = [];

  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    public translate: TranslateService
  ) {
    this.translate.get('LIST_SPECIALIZZANDI')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'specializzando_nome',
              name: res.NOME,
              style: OGListStyleType.BOLD
            },
            {
              column: 'anno_scuola',
              name: res.ANNO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'nome_coorte',
              name: res.COORTE,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'tutor_in_turno',
              name: res.TUTOR_IN_TURNO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'tipo_tutor',
              name: res.IN_TURNO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'registrate',
              name: res.REGISTRATE,
              style: OGListStyleType.NORMAL,
              align: 'center',
            },
            {
              column: 'inviate',
              name: res.INVIATE,
              style: OGListStyleType.NORMAL,
              align: 'center'
            },
            {
              column: 'confermate',
              name: res.CONFERMATE,
              style: OGListStyleType.NORMAL,
              align: 'center'
            },
            {
              column: 'status_text',
              name: res.STATO,
              style: OGListStyleType.CHIP,
              colorName: 'button_color',
              minWidth: '100px'
            }
          ],
          actionColumns: {
            edit: false,
            delete: false
          },
          customActions: [
            {
              icon: 'account_balance',
              name: res.SOSPENSIVE,
              type: 'sospensive',
              condition: (element) => {
                return this.main.getUserData('idruolo') === '9';
              }
            },
            {
              icon: 'account_balance',
              name: res.VISUALIZZA_REGISTRAZIONI,
              type: 'registrazioni'
            },
            {
              icon: 'list',
              name: res.VISUALIZZA_REGISTRAZIONI_NP,
              type: 'registrazioni-np',
              condition: (element) => {
                return this.main.getUserData('idruolo') !== '7';
              }
            },
            {
              icon: 'assignment',
              name: res.VALUTAZIONE,
              type: 'rate',
              condition: (element) => {
                // if ( (element.idtipo_tutor === 1 && element.idstatus === 0 ) || this.main.getUserData('idruolo') === '5') {
                if (element.idtipo_tutor === 1 || this.main.getUserData('idruolo') === '5' || this.main.getUserData('idruolo') === '9') {
                  return true;
                } else {
                  return false;
                }
              }
            },
            {
              icon: 'timer',
              name: res.CONTATORI,
              type: 'counter'
            }
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'specializzando_nome',
            pageSize: 10
          },
          search: '',
          selection: []
        };
        this.dialogFields = [
          {
            type: 'TEXT',
            text: res.DOMANDA_1
          },
          {
            type: 'SELECT',
            name: 'domanda_1',
            selectOptions: 'voti',
            placeholder: '',
            readonly: (element) => {
              return this.confermaStato === '1';
            }
          },
          {
            type: 'TEXT',
            text: res.DOMANDA_2
          },
          {
            type: 'SELECT',
            name: 'domanda_2',
            selectOptions: 'voti',
            placeholder: '',
            readonly: (element) => {
              return this.confermaStato === '1';
            }
          },
          {
            type: 'TEXT',
            text: res.DOMANDA_3
          },
          {
            type: 'SELECT',
            name: 'domanda_3',
            selectOptions: 'voti',
            placeholder: '',
            readonly: (element) => {
              return this.confermaStato === '1';
            }
          }
        ];
        this.selectOptions.voti = [
          { id: '1', text: res.VOTO_1 },
          { id: '2', text: res.VOTO_2 },
          { id: '3', text: res.VOTO_3 },
          { id: '4', text: res.VOTO_4 },
          { id: '5', text: res.VOTO_5 },
        ];
        this.dialogFieldsTurni = [
          {
            type: 'SELECT',
            name: 'id',
            selectOptions: 'turni',
            placeholder: res.TURNO
          }
        ];
      });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle('Studenti', '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.settings.search = search;
        this.getData(true);
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData(true, true);
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
    this.specializzandiTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/specializzandi`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
      tt: this.trainerTutor,
      idScuola: this.idScuolaFilter
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.specializzandiTable.firstPage();
        }
        if (res.scuole) {
          this.scuole = res.scuole;
        }
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }

  operations(e) {
    switch (e.type) {
      case 'sospensive':
        this.router.navigate(['/sospensive', e.element.id]);
        break;
      case 'rate':
        this.openValutazione(e.element.id);
        break;
      case 'registrazioni':
        this.router.navigate(['attivita-list'], { queryParams: { idSpecializzando: e.element.id } });
        break;
      case 'registrazioni-np':
        this.router.navigate(['attivita-list-np'], { queryParams: { idSpecializzando: e.element.id } });
        break;
      case 'counter':
        this.router.navigate(['export'], { queryParams: { idSpecializzando: e.element.id } });
        break;
      default:
        break;
    }
  }

  openValutazione(id: string, data = {}, idRecord?: string, idTurno?: string, idValutazione?: string) {
    if (Object.entries(data).length > 0) {
      this.dataModal(data)
        .subscribe((res2) => {
          this.setData(id, res2, idTurno, idValutazione);
        });
    } else {
      const ruolo = this.main.getUserData('idruolo') === '7' ? 'tutor' : 'direttore';
      const obj: Rest = {
        type: 'GET',
        path: `${this.path}/${ruolo}/${id}`,
        queryParams: {}
      };
      // INFO: Per il tutor è idturno, per il direttore è idvalutazione
      if (idRecord)  {
        obj.queryParams.id = idRecord;
      }
      this.main.rest(obj)
        .then((res: any) => {
          if (res.turni)  {
            this.selectOptionsTurni.turni = res.turni;
            this.dataModalTurni()
            .subscribe((idt: string) => {
              this.openValutazione(id, data, idt);
            });
          } else {
            this.confermaStato = res.conferma_stato;
            this.dataModal(res.valutazione, res.new, res.conferma_stato)
              .subscribe((res2) => {
                this.setData(id, res2, res.idturno, res.id);
              });
          }
        });
    }
  }

  dataModalTurni(): Observable<any> {
    return new Observable((observer) => {
      this.ogModalTurni.openModal(this.translated.SCEGLI_TURNO, '', {}, this.translated.APRI)
        .subscribe((res: any) => {
          if (res.event === 'confirm') {
            observer.next(res.data.id);
            observer.complete();
          }
        }, (err) => {
          observer.complete();
        });
    });
  }

  dataModal(data: any, first?: boolean, confermaStato?: string): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.VALUTAZIONE, '', data, ((first || confermaStato === '0') && this.main.getUserData('idruolo') !== '9') ? (confermaStato === '0' ? this.translated.CONFERMA : this.translated.SALVA) : 'no-show')
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

  setData(id: string, body: any, idTurno?: string, idValutazione?: string) {
    const obj: Rest = {
      type: 'POST',
      path: `${this.path}/${id}`,
      body,
      queryParams: {}
    };
    if (idTurno) {
      obj.queryParams.idturno = idTurno;
    }
    if (idValutazione) {
      obj.queryParams.idvalutazione = idValutazione;
    }
    this.main.rest(obj)
      .then(() => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, 'Ok')
          .then(() => {
            this.openValutazione(id, body, idTurno, idValutazione);
          }, () => { });
      });
  }

  isAteneo() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole) {
      let idRoleNum = parseInt(idRole, 10);
      if (idRoleNum >= 2 && idRole <= 4) {
        return true;
      }
    }
    return false;
  }

  isTutor() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 7) {
      return true;
    }
  }

}
