import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields, OGModalEvents, DialogResponse,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subject, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-attivita-schema',
  templateUrl: './attivita-schema.component.html',
  styleUrls: ['./attivita-schema.component.scss']
})
export class AttivitaSchemaComponent implements OnInit, OnDestroy {

  path = 'registrazioni_schema';
  idScuola: string;
  idCoorte: string;
  idAttivita: string;

  @ViewChild('attivitaSchemaTable') attivitaSchemaTable: OGListComponent;
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
      },
      {
        column: 'combo_text',
        name: 'Combo',
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
    this.idAttivita = this.aRoute.snapshot.paramMap.get('idAttivita');
    this.idCoorte = this.aRoute.snapshot.paramMap.get('idCoorte');
  }

  ngOnInit() {
    this.comboGet();
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle('Schema attività', '');
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
    this.attivitaSchemaTable.clearSelection();
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
          this.attivitaSchemaTable.firstPage();
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
    this.dialog.openConfirm('Elimina attività', 'Sei sicuro di voler eliminare l\'attività ' + name + '?', 'ELIMINA', 'Annulla')
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
    if (data.attivita_list)  {
      this.selectOptions.attivita_list = data.attivita_list;
    }
    if (data.prestazioni_list)  {
      this.selectOptions.prestazioni_list = data.prestazioni_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda attività', '', data)
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
          required: () => false
        });
        this.selectOptions[v.id] = v.opzioni;
      });
    }
    if (combo.implicite.length > 0)  {
      this.dialogFields.push({
        type: 'TITLE',
        title: 'Combo implicite'
      });
      combo.implicite.forEach((v) => {
        // Aggiungo alla modale le select per le combo ricevute
        this.dialogFields.push({
          type: 'SELECT',
          placeholder: v.nome,
          name: v.id,
          selectOptions: v.id,
          required: () => false
        });
        this.selectOptions[v.id] = v.opzioni;
      });
    }
  }

  // async prestazioniAutocomplete(search: string) {
  //   const obj: Rest = {
  //     type: 'GET',
  //     path: `prestazioni_autocomplete/${this.idScuola}/${search}`
  //   };
  //   this.main.rest(obj)
  //     .then((res: any) => {
  //       this.selectOptions.prestazioni_list = res;
  //     }, (err) => {
  //   });
  // }

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

  goBack()  {
    this.router.navigate([`/piano-di-studi/${this.idScuola}/coorti/${this.idCoorte}/attivita`]);
  }

}
