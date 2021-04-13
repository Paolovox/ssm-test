import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';
import { OGModalListComponent, DialogListItem, DialogListEvents } from 'src/app/components/ogmodal-list/ogmodal-list.component';

@Component({
  selector: 'app-attivita-combo',
  templateUrl: './attivita-combo.component.html',
  styleUrls: ['./attivita-combo.component.scss']
})
export class AttivitaComboComponent implements OnInit, OnDestroy {

  path = 'registrazioni_combo';
  registrazioniComboItems = 'registrazioni_combo_items';
  idScuola: string;

  @ViewChild('attivitaComboTable') attivitaComboTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  @ViewChild('OGModalList') ogModalList: OGModalListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  listItems: Array<DialogListItem>;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Nome combo',
        style: OGListStyleType.BOLD
      }
    ],
    customActions: [
      {
        name: 'Lista opzioni',
        type: 'options',
        icon: 'person'
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

  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome combo',
      name: 'nome'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router
  ) {
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle('Lista combo attività', '');
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
    this.attivitaComboTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
      idtipo: 2
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.attivitaComboTable.firstPage();
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
        this.delete(e.element.id, e.element.name);
        break;
      case 'options':
        this.optionsList(e.element.id);
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
    this.dialog.openConfirm('Elimina combo attività', 'Sei sicuro di voler eliminare la \'combo attività\' '
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

  optionsList(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.registrazioniComboItems}/${this.idScuola}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.listItems = res;
        this.ogModalList.openModal('Seleziona opzioni per la lista')
          .subscribe((obs) => {
            if (obs.event === DialogListEvents.ADD) {
              this.addOption(id, obs.data);
            } else if (obs.event === DialogListEvents.DELETE) {
              this.removeOption(id, obs.data);
            } else if (obs.event === DialogListEvents.EDIT) {
              this.editOption(id, obs.data);
            }
          });
      }, (err) => {
    });
  }

  addOption(id: string, nome: any) {
    const obj: Rest = {
      type: 'PUT',
      path: `${this.path}/${this.registrazioniComboItems}/${this.idScuola}/${id}`,
      body: {
        nome
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.listItems = res;
      }, (err) => {
    });
  }

  editOption(id: string, data: any) {
    const obj: Rest = {
      type: 'POST',
      path: `${this.path}/${this.registrazioniComboItems}/${this.idScuola}/${id}`,
      body: {
        id: data.id,
        nome: data.nome
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.listItems = res;
      }, (err) => {
    });
  }

  removeOption(id, item: any) {
    const obj: Rest = {
      type: 'DELETE',
      path: `${this.path}/${this.registrazioniComboItems}/${this.idScuola}/${id}/${item.id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.listItems = res;
      }, (err) => {
    });
  }

  dataModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda combo attività', '', data)
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
    body.idtipo = 2;
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
