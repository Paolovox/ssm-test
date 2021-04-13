import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent,
  OGListComponent, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
  selector: 'app-scuole',
  templateUrl: './scuole.component.html',
  styleUrls: ['./scuole.component.scss']
})
export class ScuoleComponent implements OnInit, OnDestroy {

  path = 'scuole';

  @ViewChild('scuoleTable') scuoleTable: OGListComponent;
  data: any;
  router$: Subscription;
  search$: Subscription;

  settings = {
    columns: [
      {
        column: 'nome_scuola',
        name: 'Nome scuola',
        style: OGListStyleType.BOLD
      },
      {
        column: 'classe_text',
        name: 'Classe',
        style: OGListStyleType.NORMAL
      },
      {
        column: 'numero_anni',
        name: 'Anni',
        style: OGListStyleType.NORMAL
      }
    ],
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome_scuola',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  @ViewChild('OGModal') ogModal: OGModalComponent;
  selectOptions = {
    classiList: Array<{ id: string, text: string }>(),
    anniList: [
      { text: '1', id: '1' },
      { text: '2', id: '2' },
      { text: '3', id: '3' },
      { text: '4', id: '4' },
      { text: '5', id: '5' },
      { text: '6', id: '6' }
    ]
  };
  dialogFields: Array<DialogFields> = [
    {
      type: 'INPUT',
      placeholder: 'Nome scuola',
      name: 'nome_scuola'
    },
    {
      type: 'SELECT',
      placeholder: 'Classe',
      name: 'idpds_classe',
      selectOptions: 'classiList'
    },
    {
      type: 'SELECT',
      placeholder: 'Anni',
      name: 'numero_anni',
      selectOptions: 'anniList'
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
    this.pageTitleService.setTitle('Scuole di specializzazione', '');
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
    this.router$.unsubscribe();
  }

  getData(reset = false, loading = true) {
    if (loading) {
      this.main.loaderOn();
    }
    this.scuoleTable.clearSelection();
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
        // this.dataSource = new MatTableDataSource<any>(this.data);
        if (loading) {
          this.main.loaderOff();
        }
        if (reset) {
          this.scuoleTable.firstPage();
          // this.firstPage();
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
        this.delete(e.element.id, e.element.nome_scuola);
        break;
      default:
        break;
    }
  }

  edit(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${id}`
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
        path: `${this.path}/0`
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
    this.dialog.openConfirm('Elimina scuola', 'Sei sicuro di voler eliminare la scuola ' + name + '?', 'ELIMINA', 'Annulla')
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

  dataModal(data: any): Observable<any> {
    if (data.pds_classi_list) {
      this.selectOptions.classiList = data.pds_classi_list;
    }
    return new Observable((observer) => {
      this.ogModal.openModal('Scheda scuola', '', data)
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
      path: `${this.path}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${id}`;
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






  // updateTable(e) {
  //   this.getData();
  // }

  // view(id: string) {
  //   const obj: Rest = {
  //     type: 'GET',
  //     path: `${this.path}/${id}`
  //   };
  //   this.main.rest(obj)
  //     .then((res: any) => {
  //       this.condominioModal(res)
  //         .subscribe((res2: any) => {
  //           const obj2: Rest = {
  //             type: 'POST',
  //             path: `${this.path}/${id}`,
  //             body: res2
  //           };
  //           this.main.rest(obj2)
  //             .then(() => {
  //               this.getData();
  //             }, (err) => {
  //                 this.dialog.openConfirm('Attenzione', err.error, 'Ok')
  //                   .then(() => {
  //                     this.view(id);
  //                   }, () => { });
  //             });
  //         });
  //     });
  // }

  // add(data = {}) {
  //   this.condominioModal(data)
  //     .subscribe((res: any) => {
  //       this.setData(res);
  //     });
  // }

  // delete(id: string, name: string) {
  //   this.dialog.openConfirm('Elimina scuola', 'Sei sicuro di voler eliminare la scuola ' + name + '?', 'ELIMINA', 'Annulla')
  //     .then(() => {
  //       const obj: Rest = {
  //         type: 'DELETE',
  //         path: `${this.path}/${id}`
  //       };
  //       this.main.rest(obj)
  //         .then((res: any) => {
  //           this.getData();
  //         }, (err) => {
  //           this.dialog.openConfirm('Attenzione', err.error, 'Chiudi');
  //         });
  //     }, (err) => {
  //     });
  // }

  // condominioModal(data: any): Observable<any> {
    // if (data.pds_classi_list)  {
    //   this.selectOptions.classiList = data.pds_classi_list;
    // }
  //   return new Observable((observer) => {
  //     this.ogModal.openModal('Scheda scuola', '', data)
  //       .subscribe((res: any) => {
  //         if (res.event === 'confirm')  {
  //           observer.next(res.data);
  //           observer.complete();
  //         }
  //       }, (err) => {
  //         observer.complete();
  //       });
  //   });
  // }

  // setData(body: any) {
  //   const obj: Rest = {
  //     path: `${this.path}`,
  //     type: 'PUT',
  //     body
  //   };
  //   this.main.rest(obj)
  //     .then((res: any) => {
  //       this.getData();
  //     }, (err) => {
  //       this.dialog.openConfirm('Attenzione', err.error, 'Ok')
  //         .then(() => {
  //           this.add(body);
  //         }, () => {
  //         });
  //     });
  // }

}
