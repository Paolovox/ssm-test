import { Component, OnInit, ViewChild, OnDestroy, TemplateRef, AfterViewInit } from '@angular/core';
import { PageTitleService } from '../../core/page-title/page-title.service';
import { MainUtilsService, Rest, Dialog, DialogFields, OGModalComponent,
   OGListSettings, OGListComponent, OGListStyleType } from '@ottimis/angular-utils';
import 'moment/min/locales';
import { SearchService } from 'src/app/core/search/search.service';
import { Observable, Subscription } from 'rxjs';
import { debounceTime, filter } from 'rxjs/operators';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
   selector: 'app-presidi',
   templateUrl: './presidi.component.html',
   styleUrls: ['./presidi.component.scss']
})
export class PresidiComponent implements OnInit, OnDestroy, AfterViewInit {

   path = 'presidi';

   @ViewChild('autocompleteSelect') autocompleteSelect: TemplateRef<any>;
   @ViewChild('presidiTable') presidiTable: OGListComponent;
   data: any;
   router$: Subscription;
   search$: Subscription;
   aziendeList: Array<any>;
   indirizzo: any;

   settings: OGListSettings = {
      columns: [
         {
            column: 'nome',
            name: 'Nome presidio',
            style: OGListStyleType.BOLD
         },
         {
            column: 'nome_azienda',
            name: 'Nome azienda',
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
      aziende_list: Array<{ id: string, text: string }>()
   };
   dialogFields: Array<DialogFields> = [];

   constructor(
      private pageTitleService: PageTitleService,
      private main: MainUtilsService,
      private dialog: Dialog,
      private searchService: SearchService,
      private router: Router
   ) {
   }

   ngOnInit() {
      this.pageTitleService.setTitle('Presidi', '');
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

   ngOnDestroy()  {
      this.searchService.clear();
      this.search$.unsubscribe();
      this.router$.unsubscribe();
   }

   ngAfterViewInit() {
      this.dialogFields = [
         {
            type: 'INPUT',
            placeholder: 'Nome presidio',
            name: 'nome'
         },
         {
            type: 'SELECT',
            selectOptions: 'aziende_list',
            placeholder: 'Azienda',
            name: 'idazienda'
         },
         {
            type: 'CUSTOM',
            template: this.autocompleteSelect
         }
      ];
   }

   getData(reset = false, loading = true) {
      if (loading)   {
         this.main.loaderOn();
      }
      this.presidiTable.clearSelection();
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
            this.presidiTable.firstPage();
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
            this.delete(e.element.id, e.element.nome);
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
      this.dialog.openConfirm('Elimina presidio', 'Sei sicuro di voler eliminare il presidio ' + name + '?', 'ELIMINA', 'Annulla')
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
      if (data.aziende_list)  {
         this.selectOptions.aziende_list = data.aziende_list;
      }
      return new Observable((observer) => {
         this.ogModal.openModal('Scheda presidio', '', data)
            .subscribe((res: any) => {
               this.aziendeList = data.aziende_list;
               if (res.event === 'confirm')  {
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
}

