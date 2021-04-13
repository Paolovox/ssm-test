import { Component, OnInit, ViewChild, OnDestroy, AfterViewInit, ViewEncapsulation } from '@angular/core';
import {
  MainUtilsService,
  Dialog,
  Rest,
  DialogFields,
  OGListSettings,
  OGListStyleType,
  OGListComponent,
  OGModalComponent,
  DialogResponse } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { moveItemInArray, CdkDragDrop } from '@angular/cdk/drag-drop';
import { SearchService } from 'src/app/core/search/search.service';

@Component({
  selector: 'app-contatori',
  templateUrl: './contatori.component.html',
  styleUrls: ['./contatori.component.scss'],
  encapsulation: ViewEncapsulation.None
})
export class ContatoriComponent implements OnInit, AfterViewInit, OnDestroy {

  path = 'pds_coorti/contatori';

  data: any;
  router$: Subscription;
  search$: Subscription;
  idScuola: string;
  idCoorte: string;

  dragDrop = false;

  selects = [];
  dataCounter = {
    id: '',
    nome: '',
    qty: undefined,
    data: [],
    autonomia: [],
    frequenza: false
  };

  @ViewChild('contatoriTable') contatoriTable: OGListComponent;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Autonomia',
        style: OGListStyleType.BOLD
      },
      {
        column: 'struttura_text',
        name: 'Struttura',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'quantita',
        name: 'Quantità',
        style: OGListStyleType.NORMAL
      }
    ],
    customActions: [
      {
        name: 'Filtro autonomia',
        type: 'autonomia',
        icon: 'speed'
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

  @ViewChild('OGModal', { static: false }) ogModal: OGModalComponent;

  selectOptions = {
    autonomiaList:  [
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

  ngAfterViewInit(): void {
    this.getData(true, false);
  }

  ngOnDestroy() {
    this.searchService.clear();
    this.search$.unsubscribe();
    this.router$.unsubscribe();
  }

  resetDrag() {
    this.dragDrop = false;
    this.dataCounter.data = [];
    this.selects = [];
    delete (this.dataCounter.qty);
  }

  add(save?: boolean) {
    if (save) {
      const id = this.dataCounter.id !== '' ? this.dataCounter.id : 0;
      delete(this.dataCounter.id);
      const obj: Rest = {
        type: 'PUT',
        path: `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`,
        body: this.dataCounter
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.resetDrag();
          this.getData();
        }, (err) => {
      });
    } else {
      this.dragDrop = true;
      this.dataCounter = {
        id: '',
        nome: '',
        qty: undefined,
        data: [],
        autonomia: [],
        frequenza: false
      };
      const obj: Rest = {
        type: 'GET',
        path: `${this.path}/${this.idScuola}/${this.idCoorte}/0`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.selects = res.attivita_list;
        }, (err) => {
      });
    }
  }

  drop(event: CdkDragDrop<any>, list: boolean) {
    if (!list && !event.isPointerOverContainer) {
      event.container.data.splice(event.previousIndex, 1);
    }
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
    } else {
      const el = JSON.parse(JSON.stringify(event.previousContainer.data[event.previousIndex]));
      el.added = true;
      // Per autoselezionare tutte le voci della select
      // if (el.options) {
      //   el.idvalue = new Array();
      //   el.options.forEach((option) => {
      //     el.idvalue.push(option.id);
      //   });
      // }
      event.container.data.splice(event.currentIndex, 0, el);
    }
  }

  selectAll(index) {
    this.dataCounter.data[index].idvalue = [];
    this.dataCounter.data[index].options.forEach((option) => {
      this.dataCounter.data[index].idvalue.push(option.id);
    });
  }

  unselectAll(index) {
    this.dataCounter.data[index].idvalue = [];
  }

  rangeModal(index?: number) {
    this.ogModal.openModal('Livello autonomia', '', index !== null ? this.dataCounter.autonomia[index] : {})
      .subscribe((res: DialogResponse) => {
        if (res.event === 'confirm') {
          if (index !== undefined)  {
            this.dataCounter.autonomia[index] = res.data;
          } else {
            this.dataCounter.autonomia.push(res.data);
          }
          this.dataCounter.autonomia.sort((a, b) => {
            return a.livello_da - b.livello_da;
          });
        }
      }, (err) => {
      });
  }

  // START: Lista contatori in tabella

  getData(reset = false, loading = true) {
    if (loading) {
      this.main.loaderOn();
    }
    this.contatoriTable.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idScuola}/${this.idCoorte}`,
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
          this.contatoriTable.firstPage();
        }
      }, () => {
      });
  }

  operations(e) {
    switch (e.type) {
      case 'delete':
        this.delete(e.element.id);
        break;
      case 'edit':
        this.edit(e.element.id);
        break;
      case 'autonomia':
        this.router.navigate([`filtri/${e.element.id}`], { relativeTo: this.aRoute, queryParams: { idContatore: e.element.id } });
        break;
      default:
        break;
    }
  }

  edit(id: string)  {
    this.main.loaderOn();
    this.resetDrag();
    this.dragDrop = true;
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.selects = res.attivita_list;
        this.dataCounter.id = res.id;
        this.dataCounter.data = JSON.parse(res.struttura);
        this.dataCounter.qty = res.quantita;
        this.dataCounter.nome = res.nome;
        this.dataCounter.autonomia = res.autonomia;
        this.dataCounter.frequenza = res.frequenza;
      }, (err) => {
      });
  }

  delete(id: string) {
    this.dialog.openConfirm('Elimina contatore', 'Sei sicuro di voler eliminare il contatore?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idScuola}/${this.idCoorte}/${id}`
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

}
