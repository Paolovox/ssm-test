import { Component, OnInit , ViewChild, ViewEncapsulation } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Rest, MainUtilsService, Dialog, DialogResponse, OGModalComponent, DialogFields } from '@ottimis/angular-utils';
import { TranslateService } from '@ngx-translate/core';

@Component({
   selector: 'app-login',
   templateUrl: './login.component.html',
   styleUrls: ['./login.component.scss'],
   encapsulation: ViewEncapsulation.None,
})
export class LoginComponent implements OnInit {

   email: string;
   password: string;

   returnUrl: string;
   ssoUrl: string;

   @ViewChild('OGModal') ogModal: OGModalComponent;
   selectOptions = {
      roles: [
      ]
   };
   dialogFields: Array<DialogFields> = [];
   translated: any = {};

   constructor(
      private aRoute: ActivatedRoute,
      private main: MainUtilsService,
      private router: Router,
      private dialog: Dialog,
      public translate: TranslateService
   ) {
      this.translate.get('LOGIN')
      .subscribe((res: any) => {
         this.translated = res;
         this.dialogFields = [
            {
               type: 'SELECT',
               placeholder: res.RUOLO,
               name: 'idruolo',
               selectOptions: 'roles',
            }
         ]
      });
      this.aRoute.queryParams.subscribe((params) => {
         if (params.returnUrl)   {
            this.returnUrl = params.returnUrl;
         }
         if (params.token) {
            this.login({ token: params.token });
         }
         if (params.ticket) {
            this.main.loaderOn();
            this.verifyToken(params.ticket);
         }
      });
   }

   ngOnInit()  {
      if (this.main.isLogged())  {
         if (this.isSpec()) {
            this.router.navigate(['']);
         } else {
            this.router.navigate(['specializzandi-list']);
         }
      } else {
         this.getSSO();
      }
   }

   login(data: any) {
      data.app = 2;
      const obj: Rest = {
         type: 'POST',
         path: 'user/login',
         body: data
      };
      this.main.rest(obj)
         .then((res: any) => {
            if (res.roles) {
               this.selectOptions.roles = res.roles;
               this.selectRoles(data);
            } else {
               this.main.login(res)
                  .then(() => {
                     if (this.returnUrl) {
                        if (this.returnUrl === '/attivita-list' && !this.isSpec())   {
                           this.router.navigate(['specializzandi-list']);
                        } else {
                           this.router.navigate([this.returnUrl]);
                        }
                     } else {
                        if (this.isSpec()) {
                           this.router.navigate(['']);
                        } else {
                           this.router.navigate(['specializzandi-list']);
                        }
                     }
                  });
            }
         }, (err) => {
            this.dialog.openConfirm('Attenzione', err.error, 'Ok');
      });
   }

   selectRoles(body: any = {}, cas = false, token: string = '') {
      this.ogModal.openModal(this.translated.SELEZIONA_RUOLO, '', {}, this.translated.ACCEDI)
         .subscribe(async (res: DialogResponse) => {
            if (res.event === 'confirm') {
               let path = 'user/login';
               if (cas) {
                  path = `users/v2/login/cas/${token}`;
               }
               body.idruolo = res.data.idruolo;
               const obj: Rest = {
                  type: 'POST',
                  path,
                  body
               };
               this.main.rest(obj)
                  .then((login) => {
                     this.main.login(login)
                        .then(() => {
                           if (this.returnUrl) {
                              console.log(this.returnUrl);
                              if (this.returnUrl === '/attivita-list' && !this.isSpec()) {
                                 this.router.navigate(['specializzandi-list']);
                              } else {
                                 this.router.navigate([this.returnUrl]);
                              }
                           } else {
                              if (this.isSpec()) {
                                 this.router.navigate(['']);
                              } else {
                                 this.router.navigate(['specializzandi-list']);
                              }
                           }
                        });
                  }, (err) => {
                     this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, 'Ok');
                  });
            }
         }, (err) => {
         });
   }

   verifyToken(token: string) {
      const obj: Rest = {
         type: 'POST',
         path: `users/v2/login/cas/${token}`,
      };
      this.main.rest(obj)
         .then((res: any) => {
            if (res.roles) {
               this.selectOptions.roles = res.roles;
               this.selectRoles({}, true, res.token);
            } else {
               this.main.login(res)
                  .then(() => {
                     if (this.returnUrl) {
                        if (this.returnUrl === '/attivita-list' && !this.isSpec()) {
                           this.router.navigate(['specializzandi-list']);
                        } else {
                           this.router.navigate([this.returnUrl]);
                        }
                     } else {
                        if (this.isSpec()) {
                           this.router.navigate(['']);
                        } else {
                           this.router.navigate(['specializzandi-list']);
                        }
                     }
                  });
            }
         }, (err) => {
            this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, 'Ok');
         });
   }

   isSpec() {
      const idRole = this.main.getUserData('idruolo');
      if (idRole && parseInt(idRole, 10) === 8) {
         return true;
      }
   }

   getSSO() {
      const domain = window.location.hostname;
      const obj: Rest = {
         type: 'GET',
         path: `atenei/url/sso`,
         queryParams: {
            domain
         },
         responseType: 'text/plain'
      };
      this.main.rest(obj)
         .then((res: any) => {
            this.ssoUrl = res.url;
         }, (err) => {
      });
   }
}
