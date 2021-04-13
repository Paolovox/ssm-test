import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { fadeInAnimation } from 'src/app/core/route-animation/route.animation';
import * as moment from 'moment';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-list-attivita',
  templateUrl: './list-attivita.component.html',
  styleUrls: ['./list-attivita.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation],
  encapsulation: ViewEncapsulation.None
})
export class ListAttivitaComponent implements OnInit, OnDestroy {

  path = 'specializzando_registrazioni';
  idScuola: string;
  idSpecializzando: string;

  @ViewChild('registrazioniTable') registrazioniTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  oreValutate: any;

  trainerTutor = 2;

  statusList: Array<any>;
  tutorList: Array<any>;
  attivitaList: Array<any>;
  prestazioniList: Array<any>;
  coortiList: Array<any>;
  anniList: Array<any>;

  confermaStato = '';
  idPrestazione = '';
  idAttivita = '';
  dataAttivita = '';
  idTutor = '';
  idCoorte = '';
  idAnno = '';

  contatori: Array<any>;

  settings: OGListSettings;
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
    this.translate.get('LIST_ATTIVITA')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'prestazione_text',
              name: res.PRESTAZIONE,
              style: OGListStyleType.BOLD
            },
            {
              column: 'attivita_text',
              name: res.ATTIVITA,
              style: OGListStyleType.BOLD
            },
            {
              column: 'tutor_nome',
              name: res.TUTOR,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'data_registrazione_text',
              name: res.DATA,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'quantita',
              name: res.NUMERO_ATTIVITA,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'conferma_stato_text',
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
              icon: 'create',
              name: res.MODIFICA,
              type: 'edit',
              condition: (element) => {
                if (element.conferma_stato === '0') {
                  return true;
                } else {
                  return false;
                }
              }
            },
            {
              icon: 'remove_red_eye',
              name: res.VISUALIZZA,
              type: 'view',
              condition: () => {
                if (!this.isSpec()) {
                  return true;
                } else {
                  return false;
                }
              }
            },
            {
              icon: 'send',
              name: res.INVIA_ESAME,
              type: 'sendTutor',
              condition: (element) => {
                if (element.conferma_stato === '0') {
                  return true;
                } else {
                  return false;
                }
              }
            },
            {
              icon: 'clear',
              name: res.RIFIUTA,
              type: 'decline',
              condition: (element) => {
                if (element.idtipo_tutor === '1' && this.isTutor()) {
                  return false;
                }
                if (this.isSpec() || this.isSegr()) {
                  return false;
                } else {
                  if (element.conferma_stato === '1') {
                    return true;
                  }
                }
                return false;
              }
            },
            {
              icon: 'keyboard_return',
              name: res.TORNA_SPECIALIZZANDO,
              type: 'return',
              condition: (element) => {
                if (element.idtipo_tutor === '1' && this.isTutor()) {
                  return false;
                }
                if (this.isSpec() || this.isSegr()) {
                  return false;
                } else {
                  if (element.conferma_stato === '1') {
                    return true;
                  }
                }
                return false;
              }
            },
            {
              icon: 'check',
              name: res.CONFERMA,
              type: 'confirm',
              condition: (element) => {
                if (element.idtipo_tutor === '1' && this.isTutor()) {
                  return false;
                }
                if (this.isSpec() || this.isSegr()) {
                  return false;
                } else {
                  if (element.conferma_stato === '1') {
                    return true;
                  }
                }
                return false;
              }
            },
            {
              icon: 'delete',
              name: res.ELIMINA,
              type: 'delete',
              condition: (element) => {
                if (element.conferma_stato === '0' || this.isSegr()) {
                  return true;
                } else {
                  return false;
                }
              }
            }
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'desc',
            sort: 'data_registrazione_text',
            pageSize: 20
          },
          search: '',
          selection: []
        }
      });
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idSpecializzando = params.get('idSpecializzando');
    });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle(this.translated.MIE_ATTIVITA, '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.settings.search = search;
        this.getData(true, false);
        // this.getContatori();
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData(true, false);
      // this.getContatori();
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
    this.registrazioniTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
      idspecializzando: this.idSpecializzando,
      trainerTutor: this.trainerTutor,
      conferma_stato: this.confermaStato,
      idprestazione: this.idPrestazione,
      idattivita: this.idAttivita,
      idtutor: this.idTutor,
      idcoorte: this.idCoorte,
      idanno: this.idAnno,
      data_registrazione: this.dataAttivita ? moment(this.dataAttivita).format('YYYY-MM-DD') : ''
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.attivitaList = res.attivita_list;
        this.prestazioniList = res.prestazioni_list;
        this.statusList = res.status_list;
        this.tutorList = res.tutor_list;
        if (res.ore_valutate) {
          this.oreValutate = res.ore_valutate;
        }
        if (res.coorti_list)  {
          this.coortiList = res.coorti_list;
          this.anniList = res.anni_list;
        }
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.registrazioniTable.firstPage();
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
        this.router.navigate(['attivita', e.element.id]);
        break;
      case 'view':
        this.router.navigate(['attivita', e.element.id], { queryParams: { idSpecializzando: this.idSpecializzando } } );
        break;
      case 'delete':
        this.delete(e.element.id, e.element.struttura_text);
        break;
      case 'sendTutor':
        this.statusSet(e.element.id, e.element.prestazione_text, e.element.data_registrazione_text, 1);
        break;
      case 'confirm':
        this.statusSet(e.element.id, e.element.prestazione_text, e.element.data_registrazione_text, 2);
        break;
      case 'decline':
        this.statusSet(e.element.id, e.element.prestazione_text, e.element.data_registrazione_text, 3);
        break;
      case 'return':
        this.statusSet(e.element.id, e.element.prestazione_text, e.element.data_registrazione_text, 0);
        break;
      default:
        break;
    }
  }

  resetFilter() {
    this.confermaStato = '';
    this.idAttivita = '';
    this.idPrestazione = '';
    this.idTutor = '';
    this.dataAttivita = '';
    this.getData();
  }

  async statusSet(id: string, name: string, date: string, status: number) {
    let actionText: string;
    switch (status) {
      case 0:
        actionText = this.translated.RESPINGERE;
        break;
      case 1:
        actionText = this.translated.INVIA_ESAME_MIN;
        break;
      case 2:
        actionText = this.translated.CONFERMARE;
        break;
      case 3:
        actionText = this.translated.RIFIUTARE;
        break;
      default:
        break;
    }
    const text = await this.translate.get('LIST_ATTIVITA.AZIONE_PRESTAZIONE', { actionText, name, date}).toPromise();
    this.dialog.openConfirm(this.translated.ATTENZIONE, text, 'Si', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'POST',
          path: `${this.path}/set_status/${id}/${status}`
        };
        this.main.rest(obj)
          .then((res: any) => {
            this.getData();
          }, (err) => {
          });
      }, () => {
      });
  }

  add(data = {}) {
    this.router.navigate(['attivita', 0]);
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_ATTIVITA, this.translated.ELIMINA_ATTIVITA_SUB, this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${id}`
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

  getContatori() {
    if (!this.isSpec()) {
      return;
    }
    const obj: Rest = {
      type: 'GET',
      path: `specializzando_valutazioni/export`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.contatori = res;
      }, (err) => {
    });
  }

  sendAll(status = 1) {
    this.dialog.openConfirm(this.translated.INVIA_TUTTE_IN_VERIFICA,
      this.translated.INVIA_TUTTE_IN_VERIFICA_SUB, this.translated.INVIA, this.translated.ANNULLA)
    .then(() => {
      const obj: Rest = {
        type: 'POST',
        path: `${this.path}/set_status/all/${status}`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.getData();
        }, (err) => {
      });
    });
  }

  confirmAll(status = 2) {
    this.dialog.openConfirm(this.translated.CONFERMA_TUTTE_LE_ATTIVITA,
      this.translated.CONFERMA_TUTTE_LE_ATTIVITA, this.translated.INVIA, this.translated.ANNULLA)
    .then(() => {
      const obj: Rest = {
        type: 'POST',
        path: `${this.path}/set_status/all/${status}`,
        queryParams: {
          idSpecializzando: this.idSpecializzando,
          trainerTutor: this.trainerTutor,
          conferma_stato: this.confermaStato,
          idprestazione: this.idPrestazione,
          idattivita: this.idAttivita,
          idtutor: this.idTutor,
          idcoorte: this.idCoorte,
          idanno: this.idAnno,
          data_registrazione: this.dataAttivita ? moment(this.dataAttivita).format('YYYY-MM-DD') : ''
        }
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.getData();
        }, (err) => {
      });
    });
  }

  print() {
    alert('Definire formato di stampa');
  }

  isTutor() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 7) {
      return true;
    }
  }

  isSpec() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 8) {
      return true;
    }
  }

  isDir() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 5) {
      return true;
    }
  }

  isSegr() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 9) {
      return true;
    }
  }

}
