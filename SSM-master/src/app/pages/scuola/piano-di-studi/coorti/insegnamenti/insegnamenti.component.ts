import { Component, OnInit, ViewChild, OnDestroy, AfterViewInit } from '@angular/core';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, DialogResponse,
  OGListSettings, OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-insegnamenti',
  templateUrl: './insegnamenti.component.html',
  styleUrls: ['./insegnamenti.component.scss']
})
export class InsegnamentiComponent implements OnInit, OnDestroy {

  path = 'pds_insegnamenti';

  @ViewChild('insegnamentiTable') insegnamentiTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;
  idScuola: string;
  idPianoStudi: string;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Insegnamento',
        style: OGListStyleType.BOLD
      },
      {
        column: 'docente_nome',
        name: 'Docente',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'cfu',
        name: 'Crediti',
        style: OGListStyleType.NORMAL
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

  @ViewChild('OGModal') ogModal: OGModalComponent;

  selectOptions = {
    docentiList: Array<any>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome',
      name: 'nome'
    },
    {
      type: 'SELECT',
      selectOptions: 'docentiList',
      placeholder: 'Docente',
      name: 'iddocente'
    },
    {
      type: 'INPUT',
      placeholder: 'Crediti',
      name: 'cfu'
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
    this.idPianoStudi = this.aRoute.snapshot.paramMap.get('idPianoStudi');
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
    this.insegnamentiTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idPianoStudi}`,
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
          this.insegnamentiTable.firstPage();
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
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${this.idPianoStudi}/${id}`
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
        path: `${this.path}/${this.idScuola}/${this.idPianoStudi}/0`
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
    this.dialog.openConfirm('Elimina insegnamento', 'Sei sicuro di voler eliminare l\'insegnamento?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${this.idPianoStudi}/${id}`
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
    if (data.docenti_list)  {
      this.selectOptions.docentiList = data.docenti_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda insegnamento', '', data)
        .subscribe((res: DialogResponse) => {
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
      path: `${this.path}/${this.idScuola}/${this.idPianoStudi}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${this.idPianoStudi}/${id}`;
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
