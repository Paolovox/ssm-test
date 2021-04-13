import { Component, OnInit, ViewChild, OnDestroy, AfterViewInit, ViewEncapsulation } from '@angular/core';
import {
  MainUtilsService,
  Dialog,
  Rest,
  OGListSettings,
  OGListStyleType,
  OGListComponent } from '@ottimis/angular-utils';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { moveItemInArray, CdkDragDrop } from '@angular/cdk/drag-drop';
import { SearchService } from 'src/app/core/search/search.service';

@Component({
  selector: 'app-export',
  templateUrl: './export.component.html',
  styleUrls: ['./export.component.scss'],
  encapsulation: ViewEncapsulation.None
})
export class ExportComponent implements OnInit, AfterViewInit, OnDestroy {

  path = 'pds_coorti/export';

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
    frequenza: false
  };

  @ViewChild('exportTable') exportTable: OGListComponent;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Contatore',
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


  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private searchService: SearchService,
    private dialog: Dialog,
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
      event.container.data.splice(event.currentIndex, 0, el);
    }
  }

  // START: Lista contatori in tabella

  getData(reset = false, loading = true) {
    if (loading) {
      this.main.loaderOn();
    }
    this.exportTable.clearSelection();
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
          this.exportTable.firstPage();
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
        this.dataCounter.data = res.struttura;
        this.dataCounter.qty = res.quantita;
        this.dataCounter.nome = res.nome;
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

  getSelectedLists()  {
    const ar = Array();
    for (let index = 0; index < this.dataCounter.data.length; index++) {
      ar.push('cdk-drop-list-' + index);
    }
    return ar;
  }

}
