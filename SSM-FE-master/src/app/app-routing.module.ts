// tslint:disable:max-line-length
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LoginComponent } from './authentication/login/login.component';
import { AuthGuard } from '@ottimis/angular-utils';
import { ListAttivitaComponent } from './pages/attivita/list-attivita/list-attivita.component';
import { ListAttivitaNpComponent } from './pages/attivita/list-attivita-np/list-attivita-np.component';
import { AttivitaComponent } from './pages/attivita/attivita/attivita.component';
import { AttivitaNpComponent } from './pages/attivita/attivita-np/attivita-np.component';
import { ListStudentiComponent } from './pages/attivita/list-studenti/list-studenti.component';
import { ExportComponent } from './pages/attivita/export/export.component';
import { ListValutazioniComponent } from './pages/attivita/list-valutazioni/list-valutazioni.component';
import { SospensiveComponent } from './pages/utenti/sospensive/sospensive.component';
import { ListSurveyComponent } from './pages/survey/list/list.component';
import { DomandeSurveyComponent } from './pages/survey/domande/domande.component';
import { ListStudentiSurveyComponent } from './pages/survey/studenti/studenti.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { RisposteReportComponent } from './pages/survey/risposte-report/risposte-report.component';
import { ListJobTabelleComponent } from './pages/jobdescription/list/list.component';
import { JobColumnsComponent } from './pages/jobdescription/colonne/jobcolumns.component';
import { JobDatiComponent } from './pages/jobdescription/dati/dati.component';
import { StandbyComponent } from './authentication/standby/standby.component';
import { MainComponent } from './main/main.component';


const routes: Routes = [
  {
    path: '',
    redirectTo: '/attivita-list',
    pathMatch: 'full'
  },
  {
    path: '',
    component: MainComponent,
    canActivate: [AuthGuard],
    runGuardsAndResolvers: 'always',
    children: [
      {
        path: 'dashboard',
        component: DashboardComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'specializzandi-list',
        component: ListStudentiComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'sospensive/:idSpecializzando',
        component: SospensiveComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'valutazioni-list',
        component: ListValutazioniComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'export',
        component: ExportComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'attivita-list',
        component: ListAttivitaComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'attivita-list-np',
        component: ListAttivitaNpComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'attivita/:idAttivita',
        component: AttivitaComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'attivita-np/:idAttivita',
        component: AttivitaNpComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'survey',
        children: [
          {
            path: '',
            component: ListSurveyComponent
          },
          {
            path: ':idSurvey',
            component: DomandeSurveyComponent
          },
          {
            path: ':idSurvey/risposte',
            component: ListStudentiSurveyComponent
          },
          {
            path: ':idSurvey/report',
            component: RisposteReportComponent
          }
        ]
      },
      {
        path: 'jobtabelle',
        children: [
          {
            path: '',
            component: ListJobTabelleComponent
          },
          {
            path: ':idTabella/colonne',
            component: JobColumnsComponent
          },
          {
            path: ':idTabella/dati',
            component: JobDatiComponent
          }
        ]
      }
    ]
  },
  {
    path: 'login',
    component: LoginComponent,
  },
  {
    path: 'standby',
    component: StandbyComponent
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { onSameUrlNavigation: 'reload', relativeLinkResolution: 'legacy' })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
