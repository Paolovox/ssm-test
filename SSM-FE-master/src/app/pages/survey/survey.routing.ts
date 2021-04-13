import { Routes } from '@angular/router';

import { ListSurveyComponent } from './list/list.component';
import { DomandeSurveyComponent } from './domande/domande.component';
import { ListStudentiSurveyComponent } from './studenti/studenti.component';

export const SurveyRoutes: Routes = [
   {
      path: '',
      redirectTo: 'list',
      pathMatch: 'full'
   },
   {
      path: '',
      children: [
         {
            path: 'list',
            component: ListSurveyComponent
         },
         {
            path: ':idSurvey',
            component: DomandeSurveyComponent
         },
         {
            path: ':idSurvey/risposte',
            component: ListStudentiSurveyComponent
         }
      ]
   }
];
