import { Component, OnInit, Input } from '@angular/core';
import { Router } from '@angular/router';
import { MenuItems, MenuTypes } from '../../core/menu/menu-items/menu-items';
import { MainUtilsService } from '@ottimis/angular-utils';

@Component({
   // tslint:disable-next-line:component-selector
   selector: 'ms-side-bar',
   templateUrl: './side-bar.component.html',
   styleUrls: ['./side-bar.component.scss']
})

export class SideBarComponent implements OnInit {

   @Input() menuList: any;
   @Input() verticalMenuStatus: boolean;

   constructor(
      private router: Router,
      public menuItems: MenuItems,
      public main: MainUtilsService
   ) { }

   ngOnInit() {
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

   checkRole(idRoles: Array<number>) {
      const role = parseInt(this.main.getUserData('idruolo'), 10);
      if (idRoles.indexOf(role) !== -1) {
         return true;
      }
   }

   // addMenuItem is used to add a new menu into menu list.
   addMenuItem(): void {
      this.menuItems.add({
         state: 'pages',
         name: 'GENE MENU',
         type: 'sub',
         icon: 'trending_flat',
         children: [
            {state: 'blank', name: 'SUB MENU1'},
            {state: 'blank', name: 'SUB MENU2'}
         ]
      }, MenuTypes.STANDARD);
   }

   backToStandard()  {
      this.menuItems.switchMenu(MenuTypes.STANDARD);
      this.router.navigate(['/atenei', this.main.getUserData('idAteneo')]);
      this.main.deleteUserData('idScuola');
      this.main.deleteUserData('nomeScuola');
      this.main.deleteUserData('idAteneo');
   }
}
