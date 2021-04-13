import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, DialogResponse,
  OGListSettings, OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-coorti',
  templateUrl: './coorti.component.html',
  styleUrls: ['./coorti.component.scss']
})
export class CoortiComponent implements OnInit, OnDestroy {

  path = 'pds_coorti';

  @ViewChild('coortiTable') coortiTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;
  idScuola: string;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Nome',
        style: OGListStyleType.BOLD
      },
      {
        column: 'data_inizio_text',
        name: 'Data di inizio',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions: [
      {
        name: 'Autonomie',
        icon: 'timer',
        type: 'counters'
      },
      {
        name: 'Export',
        icon: 'analytics',
        type: 'export'
      },
      {
        name: 'Piano di studi',
        icon: 'account_tree',
        type: 'pianoStudi'
      },
      {
        name: 'Tipologie attività',
        icon: 'all_inbox',
        type: 'tipologie'
      },
      {
        name: 'Lista attività',
        icon: 'menu',
        type: 'attivita'
      },
      {
        name: 'Duplica coorte',
        icon: 'file_copy',
        type: 'copy'
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

  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome',
      name: 'nome'
    },
    {
      type: 'DATEPICKER',
      placeholder: 'Data di inizio',
      name: 'data_inizio'
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
    this.coortiTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}`,
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
          this.coortiTable.firstPage();
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
      case 'counters':
        this.router.navigate([e.element.id], {relativeTo: this.aRoute});
        break;
      case 'export':
        this.router.navigate([e.element.id, 'export'], {relativeTo: this.aRoute});
        break;
      case 'pianoStudi':
        this.router.navigate([e.element.id, 'piano-studi'], {relativeTo: this.aRoute});
        break;
      case 'tipologie':
        this.router.navigate([e.element.id, 'tipologie'], {relativeTo: this.aRoute});
        break;
      case 'attivita':
        this.router.navigate([e.element.id, 'attivita'], {relativeTo: this.aRoute});
        break;
      case 'copy':
        this.copyCoorte(e.element.id);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${id}`
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
        path: `${this.path}/${this.idScuola}/0`
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
    this.dialog.openConfirm('Elimina coorte', 'Sei sicuro di voler eliminare la coorte?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${id}`
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

  copyCoorte(id: string)  {
    const obj: Rest = {
      type: 'GET',
      path: `coorte_duplica/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm('Attenzione', err.error, 'Ok');
    });
  }

  dataModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda coorte', '', data)
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
      path: `${this.path}/${this.idScuola}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${id}`;
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
