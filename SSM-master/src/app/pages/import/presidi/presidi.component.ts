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
  selector: 'app-presidi',
  templateUrl: './presidi.component.html',
  styleUrls: ['./presidi.component.scss']
})
export class AssociazionePresidiComponent implements OnInit, OnDestroy {

  path = 'import/associazione/presidi';
  idAzienda: string;
  totalElement: number;
  pageElement = 0;

  azienda: any = {};
  presidiAll: Array<any> = [];
  presidi: Array<any> = [];
  aziende: Array<any> = [];

  @ViewChild('paginator') paginator: MatPaginator;
  @ViewChild('presidiAssociazioneTable') presidiAssociazioneTable: OGListComponent;
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
  }

  ngOnInit() {

    this.getAziende();

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

  getAziende() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/aziende`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.aziende = res.aziende;
      }, (err) => {
    });
  }

  getPresidi(azienda: any, e?) {
    if (azienda)  {
      this.azienda = azienda;
      this.pageElement = 0;
      if (this.paginator) {
        this.paginator.firstPage();
      }
    }
    if (e) {
      this.pageElement = e.pageIndex;
    }
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}`,
      queryParams: {
        page: this.pageElement,
        idazienda_ext: azienda ? azienda.idazienda_ext : this.azienda.idazienda_ext,
        idazienda: azienda ? azienda.idazienda : this.azienda.idazienda
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.presidi = res.presidi;
        this.presidiAll = res.presidi_all;
        this.totalElement = res.total;
      }, (err) => {
    });
  }

  savePresidi() {
    const obj: Rest = {
      type: 'PUT',
      path: `${this.path}`,
      body: this.presidiAll
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
    this.presidiAssociazioneTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}`,
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
          this.presidiAssociazioneTable.firstPage();
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
      path: `${this.path}`
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
        path: `${this.path}`
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
          path: `${this.path}`
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
      path: `${this.path}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}`;
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
        path: `attivita_combo`
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
