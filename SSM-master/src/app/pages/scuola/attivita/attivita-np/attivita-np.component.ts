import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { OGModalListComponent, DialogListItem, DialogListEvents } from 'src/app/components/ogmodal-list/ogmodal-list.component';

@Component({
  selector: 'app-attivita-np',
  templateUrl: './attivita-np.component.html',
  styleUrls: ['./attivita-np.component.scss']
})
export class AttivitaNpComponent implements OnInit, OnDestroy {

  path = 'registrazioni_attivita_np';
  idScuola: string;

  @ViewChild('attivitaNpTable') attivitaNpTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome_attivita',
        name: 'Nome attività',
        style: OGListStyleType.BOLD
      }
    ],
    customActions: [
      {
        name: 'Dati aggiuntivi',
        type: 'dati_aggiuntivi',
        icon: 'menu'
      },
      {
        name: 'Calendario',
        type: 'calendar',
        icon: 'today',
        condition: (data) => {
          return data.calendar === '1';
        }
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome_attivita',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome attività',
      name: 'nome_attivita'
    },
    {
      type: 'CHECKBOX',
      placeholder: 'Calendario',
      name: 'calendar',
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
    this.pageTitleService.setTitle('Lista attività non professionalizzanti', '');
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
    this.attivitaNpTable.clearSelection();
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
          this.attivitaNpTable.firstPage();
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
        this.delete(e.element.id, e.element.nome_attivita);
        break;
      case 'dati_aggiuntivi':
        this.router.navigate([e.element.id], {relativeTo: this.aRoute});
        break;
      case 'calendar':
        this.router.navigate([e.element.id, 'calendar'], {relativeTo: this.aRoute});
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

  delete(id: string, name: string) {
    this.dialog.openConfirm('Elimina attività non professionalizzante', 'Sei sicuro di voler eliminare l\'attività '
      + name + '?', 'ELIMINA', 'Annulla')
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

  dataModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda attività non professionalizzante', '', data)
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
