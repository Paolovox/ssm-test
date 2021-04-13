import { Component, OnInit, Input } from '@angular/core';
import { Router, NavigationEnd, ActivatedRoute } from '@angular/router';
import { MenuItems, MenuTypes } from '../../core/menu/menu-items/menu-items';
import { MainUtilsService } from '@ottimis/angular-utils';
import { TranslateService } from '@ngx-translate/core';

@Component({
   // tslint:disable-next-line:component-selector
   selector: 'ms-side-bar',
   templateUrl: './side-bar.component.html',
   styleUrls: ['./side-bar.component.scss']
})

export class SideBarComponent implements OnInit {

   @Input() menuList: any;
   @Input() verticalMenuStatus: boolean;

   user: any = {};

   constructor(
      private router: Router,
      public menuItems: MenuItems,
      public main: MainUtilsService,
      translate: TranslateService
   ) { }

   ngOnInit() {
      this.user = this.main.getUserData();
   }

   // render to the crm page
   onClick() {
      const first = location.pathname.split('/')[1];
      if (first === 'horizontal') {
         this.router.navigate(['/horizontal/dashboard/crm']);
      } else {
         this.router.navigate(['/dashboard/crm']);
      }
   }

   checkRole(idRoles: Array<number>)   {
      const role = parseInt(this.main.getUserData('idruolo'), 10);
      if (idRoles.indexOf(role) !== -1)   {
         return true;
      }
   }
}
