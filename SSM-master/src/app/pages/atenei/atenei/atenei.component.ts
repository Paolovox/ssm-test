import { Component, OnInit, ViewChild, OnDestroy } from '@angular/core';
import { PageTitleService } from '../../../core/page-title/page-title.service';
import { MainUtilsService, Rest, Dialog, DialogFields, OGModalComponent,
   OGListSettings, OGListComponent, OGListStyleType } from '@ottimis/angular-utils';
import 'moment/min/locales';
import { SearchService } from 'src/app/core/search/search.service';
import { Observable, Subscription } from 'rxjs';
import { debounceTime, filter } from 'rxjs/operators';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';

@Component({
   selector: 'app-atenei',
   templateUrl: './atenei.component.html',
   styleUrls: ['./atenei.component.scss']
})
export class AteneiComponent implements OnInit, OnDestroy {

   path = 'atenei';

   @ViewChild('ateneiTable') ateneiTable: OGListComponent;
   data: any;
   router$: Subscription;
   search$: Subscription;

   settings: OGListSettings = {
      columns: [
         {
            column: 'nome_ateneo',
            name: 'Nome ateneo',
            style: OGListStyleType.BOLD
         },
         {
            column: 'indirizzo_ateneo',
            name: 'Indirizzo',
            style: OGListStyleType.NORMAL
         },
         {
            column: 'comune_ateneo',
            name: 'Comune ateneo',
            style: OGListStyleType.NORMAL
         },
         {
            column: 'cap_ateneo',
            name: 'CAP',
            style: OGListStyleType.NORMAL
         },
         {
            column: 'mail_ateneo',
            name: 'Mail',
            style: OGListStyleType.NORMAL
         }
      ],
      actionColumns: {
         edit: false,
         delete: false
      },
      customActions: [
         {
            name: 'Modifica',
            type: 'edit',
            icon: 'create',
            condition: () => {
               return this.main.getUserData('idruolo') === '1';
            }
         },
         {
            name: 'Scuole di specializzazione',
            type: 'scuole',
            icon: 'business'
         },
         {
            name: 'Elimina',
            type: 'delete',
            icon: 'delete',
            condition: () => {
               return this.main.getUserData('idruolo') === '1';
            }
         }
      ],
      pagingData: {
         total: 0,
         page: 1,
         order: 'asc',
         sort: 'nome_ateneo',
         pageSize: 20
      },
      search: '',
      selection: []
   };

   @ViewChild('OGModal') ogModal: OGModalComponent;

   selectOptions = {
      scuole_list: Array<{ id: string, text: string }>()
   };
   dialogFields: Array<DialogFields> = [
      {
         type: 'INPUT',
         placeholder: 'Nome ateneo',
         name: 'nome_ateneo'
      },
      {
         type: 'INPUT',
         placeholder: 'Indirizzo',
         required: () => false,
         name: 'indirizzo_ateneo'
      },
      {
         type: 'INPUT',
         placeholder: 'Comune',
         required: () => false,
         name: 'comune_ateneo'
      },
      {
         type: 'INPUT',
         placeholder: 'Cap',
         required: () => false,
         name: 'cap_ateneo'
      },
      {
         type: 'INPUT',
         placeholder: 'Mail',
         required: () => false,
         name: 'mail_ateneo'
      },
      {
         type: 'INPUT',
         placeholder: 'Dominio',
         required: () => false,
         name: 'dominio'
      },
      {
         type: 'INPUT',
         placeholder: 'Url SSO',
         required: () => false,
         name: 'url_sso'
      },
      {
         type: 'INPUT',
         placeholder: 'Url helpdesk',
         required: () => false,
         name: 'url_helpdesk'
      },
      {
         type: 'SELECT',
         selectMultiple: true,
         selectOptions: 'scuole_list',
         placeholder: 'Scuole',
         required: () => false,
         name: 'id_sds'
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
      this.pageTitleService.setTitle('Atenei', '');
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
      this.ateneiTable.clearSelection();
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
         // if (res.rows.length === 1) {
         //    this.router.navigate([res.rows[0].id], {relativeTo: this.aRoute});
         // }
         this.data = res.rows;
         this.settings.pagingData.total = res.total;
         if (reset) {
            this.ateneiTable.firstPage();
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
            this.delete(e.element.id, e.element.nome_ateneo);
            break;
         case 'scuole':
            this.router.navigate(['atenei', e.element.id]);
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
      this.dialog.openConfirm('Elimina ateneo', 'Sei sicuro di voler eliminare \' ateneo ' + name + '?', 'ELIMINA', 'Annulla')
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
      this.selectOptions.scuole_list = data.scuole_list;
      return new Observable((observer) => {
         this.ogModal.openModal('Scheda ateneo', '', data)
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

