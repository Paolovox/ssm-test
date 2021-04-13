import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListStyleType, OGListSettings } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { fadeInAnimation } from 'src/app/core/route-animation/route.animation';
import { TranslateService } from '@ngx-translate/core';

import * as moment from 'moment';

@Component({
  selector: 'app-list-attivita-np',
  templateUrl: './list-attivita-np.component.html',
  styleUrls: ['./list-attivita-np.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation],
  encapsulation: ViewEncapsulation.None
})
export class ListAttivitaNpComponent implements OnInit, OnDestroy {

  path = 'specializzando_registrazioni_np';
  idScuola: string;

  oreValutate: any;

  idstato = '';
  dataAttivita = '';
  idtutor = '';
  idattivita = '';

  statusList: Array<any>;
  attivitaList: Array<any>;

  @ViewChild('registrazioniNpTable') registrazioniNpTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  settings: OGListSettings;

  idSpecializzando: string;
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
    this.translate.get('LIST_ATTIVITA_NP')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'attivita_text',
              name: res.ATTIVITA,
              style: OGListStyleType.BOLD
            },
            {
              column: 'data_registrazione_text',
              name: res.DATA,
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
                const idRole = parseInt(this.main.getUserData('idruolo'), 10);
                if (element.conferma_stato === '0' && idRole === 8) {
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
              condition: (element) => {
                const idRole = parseInt(this.main.getUserData('idruolo'), 10);
                if (element.conferma_stato === '0' && (idRole === 5 || idRole === 9)) {
                  return true;
                } else {
                  return false;
                }
              }
            },
            // {
            //   icon: 'send',
            //   name: 'Invia in esame',
            //   type: 'sendTutor',
            //   condition: (element) => {
            //     if (element.conferma_stato === '0') {
            //       return true;
            //     } else {
            //       return false;
            //     }
            //   }
            // },
            // {
            //   icon: 'clear',
            //   name: 'Rifiuta',
            //   type: 'decline',
            //   condition: (element) => {
            //     const idRole = this.main.getUserData('idruolo');
            //     if (idRole && parseInt(idRole, 10) === 8) {
            //       return false;
            //     } else {
            //       if (element.conferma_stato === '1') {
            //         return true;
            //       }
            //     }
            //     return false;
            //   }
            // },
            {
              icon: 'check',
              name: res.CONFERMA,
              type: 'confirm',
              condition: (element) => {
                const idRole = parseInt(this.main.getUserData('idruolo'), 10);
                if (idRole && idRole === 8) {
                  return false;
                } else {
                  if (element.conferma_stato === '0' && (idRole === 5 || idRole === 9)) {
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
                const idRole = parseInt(this.main.getUserData('idruolo'), 10);
                if (element.conferma_stato === '0' && idRole === 8) {
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
        };
      });
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idSpecializzando = params.get('idSpecializzando');
    });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle(this.translated.MIE_ATTIVITA_NP, '');
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
    this.registrazioniNpTable.clearSelection();
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
      idattivita: this.idattivita ? this.idattivita : '',
      conferma_stato: this.idstato ? this.idstato : '',
      data_attivita: this.dataAttivita ? moment(this.dataAttivita).format('YYYY-MM-DD') : ''
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.statusList = res.status_list;
        this.attivitaList = res.attivita_list;
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.registrazioniNpTable.firstPage();
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
        this.router.navigate(['attivita-np', e.element.id]);
        break;
      case 'view':
        this.router.navigate(['attivita-np', e.element.id], { queryParams: { idSpecializzando: this.idSpecializzando }});
        break;
      case 'delete':
        this.delete(e.element.id, e.element.attivita_text);
        break;
      // case 'sendTutor':
      //   this.statusSet(e.element.id, e.element.attivita_text, e.element.data_registrazione_text, 1);
      //   break;
      case 'confirm':
        this.statusSet(e.element.id, e.element.attivita_text, e.element.data_registrazione_text, 1);
        break;
      // case 'decline':
      //   this.statusSet(e.element.id, e.element.attivita_text, e.element.data_registrazione_text, 3);
      //   break;
      default:
        break;
    }
  }

  resetFilter() {
    this.idstato = '';
    this.idattivita = '';
    this.idtutor = '';
    this.dataAttivita = '';
    this.getData();
  }

  async statusSet(id: string, name: string, date: string, status: number) {
    let actionText: string;
    switch (status) {
      case 1:
        actionText = this.translated.CONFERMARE;
        break;
      default:
        break;
    }
    const text = await this.translate.get('LIST_ATTIVITA_NP.STATUS_ACTION', { actionText, name, date }).toPromise();
    this.dialog.openConfirm(this.translated.ATTENZIONE, text, this.translated.SI, this.translated.ANNULLA)
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
    this.router.navigate(['attivita-np', 0]);
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_ATTIVITA, this.translated.ELIMINA_ATTIVIA_SUB + ' ' + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
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

  confirmAll(status = 1) {
    this.dialog.openConfirm(this.translated.CONFERMA_TUTTE_LE_ATTIVITA,
      this.translated.CONFERMA_TUTTE_LE_ATTIVITA_SUB, this.translated.INVIA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'POST',
          path: `${this.path}/set_status/all/${status}`,
          queryParams: {
            idSpecializzando: this.idSpecializzando
          }
        };
        this.main.rest(obj)
          .then((res: any) => {
            this.getData();
          }, (err) => {
          });
      });
  }

  isSpec() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 8) {
      return true;
    }
  }

}
