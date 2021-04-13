import { Component, OnInit , ViewEncapsulation } from '@angular/core';
import { Rest, MainUtilsService } from '@ottimis/angular-utils';
import { TranslateService } from '@ngx-translate/core';

@Component({
   selector: 'app-standby',
   templateUrl: './standby.component.html',
   styleUrls: ['./standby.component.scss'],
   encapsulation: ViewEncapsulation.None,
})
export class StandbyComponent implements OnInit {

   message: string;

   constructor(
      private main: MainUtilsService,
      translate: TranslateService
   ) {
   }

   ngOnInit()  {
      this.getData();
   }

   getData()   {
      const obj: Rest = {
         type: 'GET',
         path: `standby`
      };
      this.main.rest(obj)
         .then((res: any) => {
            this.message = res.message;
         }, (err) => {
      });
   }
}
