import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { _fixedSizeVirtualScrollStrategyFactory } from '@angular/cdk/scrolling';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-dati',
  templateUrl: './dati.component.html',
  styleUrls: ['./dati.component.scss']
})
export class JobDatiComponent implements OnInit, OnDestroy {

  path = 'jobtabelle/dati';
  idTabella: string;
  canAdd: boolean;


  @ViewChild('datiTable') datiTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings: OGListSettings = {
    columns: [],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'norder',
      pageSize: 20
    },
    actionColumns: {
      drag: true
    },
    search: '',
    selection: []
  };

  selectOptions = {
  };

  dialogFields: Array<DialogFields> = [];
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('JOB_DATI')
      .subscribe((res: any) => {
        this.translated = res;
        this.dialogFields = [
          {
            type: 'INPUT',
            placeholder: res.NOME_COLONNA,
            name: 'nome_colonna'
          }
        ]
      });
  }

  ngOnInit() {
    this.idTabella = this.aRoute.snapshot.paramMap.get('idTabella');
    this.pageTitleService.setTitle(this.translated.DATI, '');
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
    this.datiTable?.clearSelection();
    const obj: Rest = {
      path: `${this.path}/${this.idTabella}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.settings.search,
      o: this.settings.pagingData.order,
      srt: this.settings.pagingData.sort,
      p: this.settings.pagingData.page,
      c: this.settings.pagingData.pageSize,
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.canAdd = res.canAdd;
        this.settings.actionColumns.disabled = !res.canAdd;
        this.settings.actionColumns.drag = res.canAdd;


        if (this.settings.columns.length === 0) {
          res.colonne.forEach((e) => {
            const a = {
              column: e.name,
              name: e.name,
              style: OGListStyleType.NORMAL,
              headerDisabled: e.headerDisabled ? e.headerDisabled : false
            };
            this.settings.columns.push(a);
          });
        }
        // this.datiTable.refreshData();
        // var n=0;
        // this.settings.columns.map( e => {
        //   if( n<res.colonne.length ) {
        //     e.name = res.colonne[n].name;
        //     e.column = res.colonne[n].column;
        //   } else {
        //     e.visible = () => {
        //       return false;
        //     }
        //   }

        //   n++;
        // });


        // console.log( this.settings.columns );
        this.data = res.rows;
        this.settings.pagingData.total = res.total;
        if (reset) {
          this.datiTable?.firstPage();
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
        this.delete(e.element.id, e.element.nome_colonna);
        break;
      case 'drag':
        this.dragItem(e.element);
        // this.dragItem(e.element.id);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idTabella}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dialogFields = res.dialogFields;
        this.dataModal(res.data)
          .subscribe((res2: any) => {
            this.setData(id, res2);
          });
      });
  }

  dragItem(element: any) {
    const obj: Rest = {
       type: 'PUT',
       path: `${this.path}/order/${this.idTabella}`,
       body: {
          cur_pos: element.previousIndex,
          des_pos: element.currentIndex,
          cur_id: element.data[element.previousIndex].id,
          des_id: element.data[element.currentIndex].id
       }
    };
    this.main.rest(obj)
       .then((res: any) => {
       }, (err) => {
    });
  }


  add(data = {}) {
    if (Object.entries(data).length > 0) {
      this.dataModal(data)
        .subscribe((res2) => {
          this.dialogFields = res2.dialogFields;
          this.setData('0', res2, true);
        });
    } else {
      const obj: Rest = {
        type: 'GET',
        path: `${this.path}/${this.idTabella}/0`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.dialogFields = res.dialogFields;
          this.dataModal(res)
            .subscribe((res2) => {
              this.setData('0', res2, true);
            });
        });
    }
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_COLONNA, this.translated.ELIMINA_COLONNA_SUB + ' '
      + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${this.idTabella}/${id}`
        };
        this.main.rest(obj)
          .then((res: any) => {
            this.getData();
          }, (err) => {
            this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.CHIUDI);
          });
      }, (err) => {
      });
  }

  dataModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal(this.translated.COLONNA, '', data)
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
      path: `${this.path}/${this.idTabella}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${this.idTabella}/${id}`;
    }
    this.main.rest(obj)
      .then(() => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.OK)
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
