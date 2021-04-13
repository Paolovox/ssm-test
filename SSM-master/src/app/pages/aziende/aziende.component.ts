import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from '../../core/page-title/page-title.service';
import { MainUtilsService, Rest, Dialog, DialogFields, OGModalComponent,
   OGListSettings, OGListComponent, OGListStyleType } from '@ottimis/angular-utils';
import 'moment/min/locales';
import { SearchService } from 'src/app/core/search/search.service';
import { Observable, Subscription } from 'rxjs';
import { debounceTime, filter } from 'rxjs/operators';
import { Router, RouterEvent, NavigationEnd } from '@angular/router';

@Component({
   selector: 'app-aziende',
   templateUrl: './aziende.component.html',
   styleUrls: ['./aziende.component.scss']
})
export class AziendeComponent implements OnInit, OnDestroy {

   path = 'aziende';

   @ViewChild('aziendeTable') aziendeTable: OGListComponent;
   data: any;
   router$: Subscription;
   search$: Subscription;

   settings: OGListSettings = {
      columns: [
         {
            column: 'nome',
            name: 'Nome azienda',
            style: OGListStyleType.BOLD
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
   dialogFields: Array<DialogFields> = [
      {
         type: 'INPUT',
         placeholder: 'Nome azienda',
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
      this.pageTitleService.setTitle('Aziende', '');
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

   getData(reset = false, loading = true) {
      if (loading)   {
         this.main.loaderOn();
      }
      this.aziendeTable.clearSelection();
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
            this.aziendeTable.firstPage();
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
      this.dialog.openConfirm('Elimina azienda', 'Sei sicuro di voler eliminare \' azienda ' + name + '?', 'ELIMINA', 'Annulla')
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
      return new Observable((observer) => {
         this.ogModal.openModal('Scheda azienda', '', data)
            .subscribe((res: any) => {
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

