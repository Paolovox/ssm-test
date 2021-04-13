import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields, OGModalEvents, DialogResponse,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subject, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { MatPaginator } from '@angular/material/paginator';

@Component({
  selector: 'app-associazione',
  templateUrl: './associazione.component.html',
  styleUrls: ['./associazione.component.scss']
})
export class AssociazioneComponent implements OnInit, OnDestroy {

  path = 'import/associazione/aziende';
  idScuola: string;
  idAttivita: string;
  totalElement: number;
  pageElement = 0;

  aziendeAll: Array<any> = [];
  aziende: Array<any> = [];

  @ViewChild('paginator') paginator: MatPaginator;
  @ViewChild('ateneiAssociazioneTable') ateneiAssociazioneTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  settings: OGListSettings = {
    columns: [
      {
        column: 'prestazione_text',
        name: 'Prestazione',
        style: OGListStyleType.BOLD
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'prestazione_text',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  selectOptions: any = {
    prestazioni_list: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'SELECT',
      placeholder: 'Prestazione',
      name: 'idprestazione',
      selectOptions: 'prestazioni_list',
      selectMultiple: true
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
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idAttivita = params.get('idAttivita');
    });
  }

  ngOnInit() {

    this.getAziende();

    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle('Filtro combo attività', '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.settings.search = search;
        // this.getData(true, false);
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      // this.getData(true, false);
    });
  }

  ngOnDestroy() {
    this.searchService.clear();
    this.search$.unsubscribe();
    this.router$.unsubscribe();
  }

  getAziende(e?) {
    if (e) {
      this.pageElement = e.pageIndex;
    }
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}`,
      queryParams: {
        page: this.pageElement
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.aziendeAll = res.aziende_all;
        this.aziende = res.aziende;
        this.totalElement = res.total;
      }, (err) => {
    });
  }

  saveAziende() {
    const obj: Rest = {
      type: 'PUT',
      path: `${this.path}`,
      body: this.aziendeAll
    };
    this.main.rest(obj)
      .then((res: any) => {
        alert('ok');
      }, (err) => {
    });
  }

  getData(reset = false, loading = true) {
    if (loading) {
      this.main.loaderOn();
    }
    this.ateneiAssociazioneTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idAttivita}`,
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
          this.ateneiAssociazioneTable.firstPage();
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
        this.delete(e.element.id, e.element.prestazione_text);
        break;
      default:
        break;
    }
  }

  async edit(id: string) {
    await this.comboGet();
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${this.idAttivita}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dialogFields.map(e => {
          if (e.type === 'SELECT' && e.name === 'idprestazione')  {
            e.selectMultiple = false;
          }
          return e;
        });
        this.dataModal(res)
          .subscribe((res2: any) => {
            this.setData(id, res2);
          });
      });
  }

  async add(data = {}) {
    if (Object.entries(data).length > 0) {
      this.dataModal(data)
        .subscribe((res2) => {
          this.setData('0', res2, true);
        });
    } else {
      this.resetModalData();
      await this.comboGet();
      const obj: Rest = {
        type: 'GET',
        path: `${this.path}/${this.idScuola}/${this.idAttivita}/0`
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
    this.dialog.openConfirm('Elimina filtro attività', 'Sei sicuro di voler eliminare il filtro ' + name + '?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${this.idAttivita}/${id}`
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
    if (data.prestazioni_list)  {
      this.selectOptions.prestazioni_list = data.prestazioni_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda filtro', '', data)
        .subscribe((res: DialogResponse) => {
          if (res.event === OGModalEvents.CONFIRM) {
            observer.next(res.data);
            observer.complete();
          }
          if (res.event === OGModalEvents.CLOSE) {
            this.resetModalData();
          }
        }, (err) => {
          observer.complete();
        });
    });
  }

  setData(id: string, body: any, insert = false) {
    const obj: Rest = {
      type: insert ? 'PUT' : 'POST',
      path: `${this.path}/${this.idScuola}/${this.idAttivita}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idScuola}/${this.idAttivita}/${id}`;
    }
    this.main.rest(obj)
      .then(() => {
        this.getData();
        this.resetModalData();
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

  async comboGet()  {
    return new Promise<void>((resolve, reject) => {
      const obj: Rest = {
        type: 'GET',
        path: `attivita_combo/${this.idAttivita}`
      };
      this.main.rest(obj)
        .then((res: any) => {
          if (!res) {
            resolve();
            return;
          }
          this.buildModalCombos(res);
          resolve();
        }, (err) => {
        });
    });
  }

  buildModalCombos(combo) {
    this.resetModalData();
    if (combo.esplicite.length > 0)  {
      this.dialogFields.push({
        type: 'TITLE',
        title: 'Combo esplicite'
      });
      combo.esplicite.forEach((v) => {
        // Aggiungo alla modale le select per le combo ricevute
        this.dialogFields.push({
          type: 'SELECT',
          placeholder: v.nome,
          name: v.id,
          selectOptions: v.id,
          required: () => false,
          selectMultiple: true
        });
        this.selectOptions[v.id] = v.opzioni;
      });
    }
  }

  resetModalData() {
    this.dialogFields = [
      {
        type: 'SELECT',
        placeholder: 'Prestazione',
        name: 'idprestazione',
        selectOptions: 'prestazioni_list',
        selectMultiple: true
      }
    ];
  }

  goBack() {
    this.router.navigate(['/attivita/list']);
  }

}
