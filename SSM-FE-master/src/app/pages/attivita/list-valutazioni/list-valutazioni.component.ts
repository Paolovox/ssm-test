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

import * as moment from 'moment';

@Component({
  selector: 'app-list-valutazioni',
  templateUrl: './list-valutazioni.component.html',
  styleUrls: ['./list-valutazioni.component.scss']
})
export class ListValutazioniComponent implements OnInit, OnDestroy {

  path = 'specializzando_valutazioni';
  idScuola: string;

  @ViewChild('valutazioniTable') valutazioniTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  filtroDal: any;
  filtroAl: any;
  search$: Subscription;
  router$: Subscription;

  anno = 0;
  anniList = [];

  settings: OGListSettings;

  selectOptions = {
    voti: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = []
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private searchService: SearchService,
    private router: Router,
    public translate: TranslateService
  ) {
    this.translate.get('LIST_VALUTAZIONI')
      .subscribe((res: any) => {
        this.translated = res;
        this.settings = {
          columns: [
            {
              column: 'nome_tutor',
              name: res.TUTOR,
              style: OGListStyleType.BOLD
            },
            {
              column: 'nome_direttore',
              name: res.DIRETTORE,
              style: OGListStyleType.BOLD
            },
            {
              column: 'anno',
              name: res.ANNO,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'data_valutazione',
              name: res.DATA,
              style: OGListStyleType.NORMAL
            },
            {
              column: 'data_affiancamento',
              name: res.DATA_AFFIANCAMENTO,
              style: OGListStyleType.NORMAL
            }
          ],
          actionColumns: {
            edit: false,
            delete: false
          },
          customActions: [
            {
              icon: 'remove_red_eye',
              name: res.VALUTAZIONE,
              type: 'rate'
            }
          ],
          pagingData: {
            total: 0,
            page: 1,
            order: 'asc',
            sort: 'id',
            pageSize: 20
          },
          search: '',
          selection: []
        }
        this.dialogFields = [
          {
            type: 'TEXT',
            text: res.DOMANDA_1
          },
          {
            type: 'SELECT',
            name: 'domanda_1',
            readonly: () => true,
            selectOptions: 'voti',
            placeholder: ''
          },
          {
            type: 'TEXT',
            text: res.DOMANDA_2
          },
          {
            type: 'SELECT',
            name: 'domanda_2',
            readonly: () => true,
            selectOptions: 'voti',
            placeholder: ''
          },
          {
            type: 'TEXT',
            text: res.DOMANDA_3
          },
          {
            type: 'SELECT',
            name: 'domanda_3',
            readonly: () => true,
            selectOptions: 'voti',
            placeholder: ''
          }
        ];
        this.selectOptions.voti = [
          { id: '1', text: res.VOTO_1 },
          { id: '2', text: res.VOTO_2 },
          { id: '3', text: res.VOTO_3 },
          { id: '4', text: res.VOTO_4 },
          { id: '5', text: res.VOTO_5 },
        ];
      });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle(this.translated.VALUTAZIONI, '');
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
    this.valutazioniTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/specializzando`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
      anno: this.anno,
      filtroDal: this.filtroDal ? moment(this.filtroDal).format('YYYY-MM-DD') : '',
      filtroAl: this.filtroAl ? moment(this.filtroAl).format('YYYY-MM-DD') : ''
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (res.anni) {
          this.anniList = res.anni;
        }
        if (reset) {
          this.valutazioniTable.firstPage();
        }
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }

  operations(e) {
    switch (e.type) {
      case 'rate':
        this.openValutazione(e.element.id);
        break;
      case 'registrazioni':
        this.router.navigate(['attivita-list'], { queryParams: { idSpecializzando: e.element.id } });
        break;
      default:
        break;
    }
  }

  openValutazione(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/specializzando/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dataModal(res.valutazione)
        .subscribe();
      });
  }

  dataModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.VALUTAZIONE, '', data)
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

  removeFilter() {
    this.filtroAl = '';
    this.filtroDal = '';
    this.getData();
  }

}
