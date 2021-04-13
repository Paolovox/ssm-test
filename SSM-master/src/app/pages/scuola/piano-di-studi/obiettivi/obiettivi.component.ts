import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
  selector: 'app-obiettivi',
  templateUrl: './obiettivi.component.html',
  styleUrls: ['./obiettivi.component.scss']
})
export class ObiettiviComponent implements OnInit, OnDestroy {

  path = 'pds_obiettivi';
  idScuola: string;

  @ViewChild('obiettiviTable') obiettiviTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings = {
    columns: [
      {
        column: 'ambito_nome',
        name: 'Nome',
        style: OGListStyleType.BOLD
      },
      {
        column: 'cfu',
        name: 'Cfu richiesti',
        style: OGListStyleType.NORMAL
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'ambito_nome',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  @ViewChild('OGModal') ogModal: OGModalComponent;

  selectOptions = {
    ambiti_disciplinari_list: Array<{ id: string, text: string }>(),
    tipologie_attivita_list: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      selectOptions: 'ambiti_disciplinari_list',
      placeholder: 'Ambiti disciplinari collegati',
      name: 'idambito'
    },
    {
      type: 'INPUT',
      inputType: 'number',
      placeholder: 'Cfu richiesti',
      name: 'cfu'
    },
    {
      type: 'SELECT',
      selectMultiple: true,
      selectOptions: 'tipologie_attivita_list',
      placeholder: 'Tipo attività formativa utilizzabile',
      name: 'idtipologie_attivita'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router
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
    this.obiettiviTable.clearSelection();
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
          this.obiettiviTable.firstPage();
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
    this.dialog.openConfirm('Elimina settore scientifico', 'Sei sicuro di voler eliminare il settore scientifico'
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
    if (data.tipologie_attivita_list)  {
      this.selectOptions.tipologie_attivita_list = data.tipologie_attivita_list;
    }
    if (data.ambiti_disciplinari_list)  {
      this.selectOptions.ambiti_disciplinari_list = data.ambiti_disciplinari_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Tipi di attività formative', '', data)
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
      path: `${this.path}/${this.idScuola}/${id}`,
      body
    };
    if (insert) {
      obj.path = `${this.path}/${this.idScuola}`;
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
          }, () => {});
      });
  }
}
