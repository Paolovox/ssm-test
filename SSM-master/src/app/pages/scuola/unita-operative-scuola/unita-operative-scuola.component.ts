import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, OGModalEvents,
  OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';

@Component({
  selector: 'app-unita-operative-scuola',
  templateUrl: './unita-operative-scuola.component.html',
  styleUrls: ['./unita-operative-scuola.component.scss']
})
export class UnitaOperativeScuolaComponent implements OnInit, OnDestroy {

  idScuola: string;
  path = 'scuole_unita';

  @ViewChild('unitaOperativaTable') unitaOperativaTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings = {
    columns: [
      {
        column: 'nome',
        name: 'Unità operativa',
        style: OGListStyleType.BOLD
      },
      {
        column: 'presidio',
        name: 'Presidio',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'tipologia_sede',
        name: 'Tipologia sede',
        style: OGListStyleType.NORMAL
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'un.nome',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  @ViewChild('OGModal') ogModal: OGModalComponent;

  selectOptions = {
    unita_list: Array<{ id: string, text: string }>(),
    tipologie_sede_list: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'AUTOCOMPLETE',
      completeOptions: 'unita_list',
      placeholder: 'Unità operativa',
      name: 'idunita'
    },
    {
      type: 'SELECT',
      selectKeyValue: 'id',
      selectKeyText: 'text',
      selectOptions: 'tipologie_sede_list',
      placeholder: 'Tipologia sede',
      name: 'idtipologia_sede'
    }
  ];

  unitaList: Array<any>;
  tipologiaSedeList: Array<any>;

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
    this.idScuola = this.aRoute.snapshot.paramMap.get('idScuola');
    this.path = `scuole_unita/${this.idScuola}`;
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
      id_sds: this.main.getUserData('idScuola')
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.unitaOperativaTable.firstPage();
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
        this.delete(e.element.id, e.element.nome);
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
    this.dialog.openConfirm('Elimina unità operativa', 'Sei sicuro di voler eliminare l\'unità operatva?'
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
    if (data.tipologie_sede_list) {
      this.selectOptions.tipologie_sede_list = data.tipologie_sede_list;
    }
    if (data.unita_list) {
      this.selectOptions.unita_list = data.unita_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Unità operativa', '', data)
        .subscribe((res: any) => {
          if (res.event === OGModalEvents.AUTOCOMPLETE_KEYPRESS) {
            this.unitaAutocomplete(res.data.target.value);
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

  async unitaAutocomplete(search: string) {
    const obj: Rest = {
      type: 'GET',
      path: `scuole_unita/unita_autocomplete/${this.idScuola}/${search}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.selectOptions.unita_list = res;
      }, (err) => {
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
