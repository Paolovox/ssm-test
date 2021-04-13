import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, DialogResponse,
  OGListSettings, OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-autonomia-filtri',
  templateUrl: './autonomia-filtri.component.html',
  styleUrls: ['./autonomia-filtri.component.scss']
})
export class AutonomiaFiltriComponent implements OnInit, OnDestroy {

  path = 'pds_registrazioni_filtri';

  @ViewChild('autonomiaTable') autonomiaTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;
  idScuola: string;
  idCoorte: string;
  idContatore: string;

  settings: OGListSettings = {
    columns: [
      {
        column: 'specializzando_nome',
        name: 'Nome specializzando',
        style: OGListStyleType.BOLD
      },
      {
        column: 'autonomia',
        name: 'Autonomia',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'livello_da',
        name: 'Da',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'livello_a',
        name: 'A',
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
    specializzandiList: Array<{id: string, text: string}>(),
    autonomiaList: [
      {
        id: 1,
        text: 1
      },
      {
        id: 2,
        text: 2
      },
      {
        id: 3,
        text: 3
      },
      {
        id: 4,
        text: 4
      },
      {
        id: 5,
        text: 5
      }
    ]
  };

  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      placeholder: 'Specializzando',
      name: 'idspecializzando',
      selectOptions: 'specializzandiList'
    },
    {
      type: 'INPUT',
      name: 'livello_da',
      placeholder: 'Da',
      inputType: 'number'
    },
    {
      type: 'INPUT',
      name: 'livello_a',
      placeholder: 'A',
      inputType: 'number'
    },
    {
      type: 'SELECT',
      name: 'autonomia',
      placeholder: 'Autonomia',
      selectOptions: 'autonomiaList'
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
    this.idContatore = this.aRoute.snapshot.paramMap.get('idContatore');
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
    this.autonomiaTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}`,
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
          this.autonomiaTable.firstPage();
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
      path: `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}/${id}`
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
        path: `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}/0`
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
    this.dialog.openConfirm('Elimina filtro', 'Sei sicuro di voler eliminare il filtro?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}/${id}`
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
    if (data.specializzandi_list) {
      this.selectOptions.specializzandiList = data.specializzandi_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Filtro autonomia', '', data)
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
      path: `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${this.idCoorte}/${this.idContatore}/${id}`;
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

  goBack() {
    const url = this.removeSlashesUrl(this.router.url, 2);
    this.router.navigate([url]);
  }

  removeSlashesUrl(url: string, nS: number)  {
    let i = 0;
    while (i < nS) {
      url = url.substr(0, url.lastIndexOf('/'));
      i++;
    }
    return url;
  }

}
