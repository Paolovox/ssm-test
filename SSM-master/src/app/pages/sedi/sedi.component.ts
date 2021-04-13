import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';
import { SelectionModel } from '@angular/cdk/collections';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { ActivatedRoute, Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
  selector: 'app-sedi',
  templateUrl: './sedi.component.html',
  styleUrls: ['./sedi.component.scss']
})
export class SediComponent implements OnInit, OnDestroy {

  path = 'sedi_scuole';

  @ViewChild('paginator') paginator: MatPaginator;
  data: any;
  router$: Subscription;
  search$: Subscription;

  displayedColumns: string[] = ['select', 'nome_sede_scuola', 'actions'];
  total: number;
  page = 1;
  order = 'asc';
  sort = 'nome_sede_scuola';
  search = '';
  dataSource;
  selection = new SelectionModel<any>(true, []);
  idAteneo: string;
  idScuola: string;

  @ViewChild('OGModal') ogModal: OGModalComponent;
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Sede scuola',
      name: 'nome_sede_scuola'
    }
  ];

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private aRoute: ActivatedRoute,
    private router: Router
  ) {
    this.dataSource = new MatTableDataSource<any>(this.data);
  }

  ngOnInit() {
    this.dataSource.paginator = this.paginator;
    this.idAteneo = this.aRoute.snapshot.paramMap.get('idAteneo');
    this.idScuola = this.aRoute.snapshot.paramMap.get('idScuola');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        // FIXME:
        // this.settings.search = search;
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
    this.selection.clear();
    const obj: Rest = {
      path: `${this.path}`,
      type: 'GET'
    };
    obj.queryParams = {
      s: this.search,
      o: this.order,
      srt: this.sort,
      p: this.page,
      c: this.paginator.pageSize,
      idateneo: this.idAteneo
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res.rows;
        this.total = res.total;
        // this.pageTitleService.setTitle(res.titolo, '/atenei');
        this.dataSource = new MatTableDataSource<any>(this.data);
        if (loading) {
          this.main.loaderOff();
        }
        if (reset) {
          this.paginator.firstPage();
        }
      }, () => {
        if (loading) {
          this.main.loaderOff();
        }
      });
  }

  view(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.viewModal(res)
          .subscribe((res2: any) => {
            const obj2: Rest = {
              type: 'POST',
              path: `${this.path}/${id}`,
              body: res2
            };
            this.main.rest(obj2)
              .then(() => {
                this.getData();
              }, (err) => {
                  this.dialog.openConfirm('Attenzione', err.error, 'Ok')
                    .then(() => {
                      this.view(id);
                    }, () => { });
              });
          });
      });
  }

  add(data = {}) {
    this.viewModal(data)
      .subscribe((res: any) => {
        this.setData(res);
      });
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm('Elimina sede', 'Sei sicuro di voler eliminare la sede '
    + name + '?', 'ELIMINA', 'Annulla')
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${id}`
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

  viewModal(data: any): Observable<any> {
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda sede', '', data)
        .subscribe((res: any) => {
          observer.next(res.data);
          observer.complete();
        }, (err) => {
          observer.complete();
        });
    });
  }

  setData(body: any) {
    body.id = 0;
    const obj: Rest = {
      path: `${this.path}`,
      type: 'PUT',
      body
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.getData();
      }, (err) => {
        this.dialog.openConfirm('Attenzione', err.error, 'Ok')
          .then(() => {
            this.add(body);
          }, () => {
          });
      });
  }

  paging(e) {
    this.page = e.pageIndex + 1;
    this.getData();
  }

  sortData(e) {
    this.sort = e.active;
    this.order = e.direction;
    this.getData();
  }

  //  Whether the number of selected elements matches the total number of rows.
  isAllSelected() {
    const numSelected = this.selection.selected.length;
    const numRows = this.dataSource.data.length;
    return numSelected === numRows;
  }

  // Selects all rows if they are not all selected; otherwise clear selection.
  masterToggle() {
    this.isAllSelected() ? this.selection.clear() : this.dataSource.data.forEach(row => this.selection.select(row.id));
  }
}
