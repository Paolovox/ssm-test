// tslint:disable:max-line-length
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LoginComponent } from './authentication/login/login.component';
import { AteneiComponent } from './pages/atenei/atenei/atenei.component';
import { AziendeComponent } from './pages/aziende/aziende.component';
import { AuthGuard } from '@ottimis/angular-utils';
import { UtentiComponent } from './pages/utenti/utenti/utenti.component';
import { ScuoleDiSpecializzazioneComponent } from './pages/atenei/scuole-di-specializzazione/scuole-di-specializzazione.component';
import { SediComponent } from './pages/sedi/sedi.component';
import { ScuoleComponent } from './pages/scuole/scuole.component';
import { ScuolaDashboardComponent } from './pages/scuola/scuola-dashboard/scuola-dashboard.component';
import { AmbitiDisciplinariComponent } from './pages/pds/ambiti-disciplinari/ambiti-disciplinari.component';
import { PresidiComponent } from './pages/presidi/presidi.component';
import { UnitaOperativeComponent } from './pages/unita-operative/unita-operative.component';
import { UnitaOperativeScuolaComponent } from './pages/scuola/unita-operative-scuola/unita-operative-scuola.component';
import { UtenteComponent } from './pages/utenti/utente/utente.component';
import { UtentiScuolaComponent } from './pages/scuola/utenti/utenti/utenti-scuola.component';
import { UtenteScuolaComponent } from './pages/scuola/utenti/utente/utente-scuola.component';
import { SettoriScientificiComponent } from './pages/pds/settori-scientifici/settori-scientifici.component';
import { AttivitaComponent } from './pages/scuola/piano-di-studi/coorti/attivita/attivita.component';
import { AttivitaComboComponent } from './pages/scuola/attivita/attivita-combo/attivita-combo.component';
import { AttivitaSchemaComponent } from './pages/scuola/attivita/attivita-schema/attivita-schema.component';
import { TurniComponent } from './pages/scuola/turni/turni.component';
import { PrestazioniComponent } from './pages/scuola/attivita/prestazioni/prestazioni.component';
import { ClassiComponent } from './pages/pds/classi/classi.component';
import { AttivitaFormativeComponent } from './pages/pds/attivita-formative/attivita-formative.component';
import { ObiettiviComponent } from './pages/scuola/piano-di-studi/obiettivi/obiettivi.component';
import { PianoStudiComponent } from './pages/scuola/piano-di-studi/coorti/piano-studi/piano-studi.component';
import { AreeComponent } from './pages/pds/aree/aree.component';
import { InsegnamentiComponent } from './pages/scuola/piano-di-studi/coorti/insegnamenti/insegnamenti.component';
import { CoortiComponent } from './pages/scuola/piano-di-studi/coorti/coorti/coorti.component';
import { ContatoriComponent } from './pages/scuola/piano-di-studi/coorti/contatori/contatori.component';
import { AttivitaTipologieComponent } from './pages/scuola/piano-di-studi/coorti/attivita-tipologie/attivita-tipologie.component';
import { AttivitaFiltriComponent } from './pages/scuola/attivita/attivita-filtri/attivita-filtri.component';
import { AutonomiaFiltriComponent } from './pages/scuola/piano-di-studi/coorti/autonomia-filtri/autonomia-filtri.component';
import { AssociazioneComponent } from './pages/import/associazione/associazione.component';
import { AssociazionePresidiComponent } from './pages/import/presidi/presidi.component';
import { AssociazioneUnitaComponent } from './pages/import/unita/unita.component';
import { AttivitaNpComponent } from './pages/scuola/attivita/attivita-np/attivita-np.component';
import { AttivitaNpDatiAggiuntiviComponent } from './pages/scuola/attivita/attivita-np-dati-aggiuntivi/attivita-np-dati-aggiuntivi.component';
import { AttivitaNpCalendarComponent } from './pages/scuola/attivita/attivita-np-calendar/attivita-np-calendar.component';
import { CopyComponent } from './pages/copy/copy.component';
import { ExportComponent } from './pages/scuola/piano-di-studi/coorti/export/export.component';
import { AssociazioneScuoleComponent } from './pages/import/scuole/scuole.component';
import { StandbyComponent } from './authentication/standby/standby.component';
import { MainComponent } from './main/main.component';

// const idRuolo = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')).idruolo : 0;

const routes: Routes = [
  {
    path: '',
    component: MainComponent,
    canActivate: [AuthGuard],
    runGuardsAndResolvers: 'always',
    children: [
      {
        path: '',
        redirectTo: 'atenei',
        pathMatch: 'full'
      },
      {
        path: 'import',
        canActivate: [AuthGuard],
        children: [
          {
            path: 'associazione-scuole',
            component: AssociazioneScuoleComponent
          },
          {
            path: 'associazione-aziende',
            component: AssociazioneComponent
          },
          {
            path: 'associazione-presidi',
            component: AssociazionePresidiComponent
          },
          {
            path: 'associazione-unita',
            component: AssociazioneUnitaComponent
          }
        ]
      },
      {
        path: 'copy',
        canActivate: [AuthGuard],
        component: CopyComponent
      },
      {
        path: 'atenei',
        component: AteneiComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'aziende',
        component: AziendeComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'presidi',
        component: PresidiComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'unita-operative',
        component: UnitaOperativeComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'attivita',
        children: [
          {
            path: 'prestazioni',
            component: PrestazioniComponent,
            canActivate: [AuthGuard]
          },
          {
            path: 'np',
            children: [
              {
                path: '',
                component: AttivitaNpComponent,
                canActivate: [AuthGuard]
              },
              {
                path: ':idAttivita',
                component: AttivitaNpDatiAggiuntiviComponent,
                canActivate: [AuthGuard]
              },
              {
                path: ':idAttivita/calendar',
                component: AttivitaNpCalendarComponent,
                canActivate: [AuthGuard]
              }
            ]
          },
          {
            path: 'combo',
            component: AttivitaComboComponent,
            canActivate: [AuthGuard]
          }
        ]
      },
      {
        path: 'pds',
        children: [
          {
            path: 'aree',
            component: AreeComponent,
            canActivate: [AuthGuard]
          },
          {
            path: 'classi',
            component: ClassiComponent,
            canActivate: [AuthGuard]
          },
          {
            path: 'ambiti-disciplinari',
            component: AmbitiDisciplinariComponent,
            canActivate: [AuthGuard]
          },
          {
            path: 'attivita-formative',
            component: AttivitaFormativeComponent,
            canActivate: [AuthGuard]
          },
          {
            path: 'settori-scientifici',
            component: SettoriScientificiComponent,
            canActivate: [AuthGuard]
          }
        ]
      },
      {
        path: 'scuole',
        component: ScuoleComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'atenei/:idAteneo',
        component: ScuoleDiSpecializzazioneComponent,
        canActivate: [AuthGuard]
      },
      // {
      //   path: 'atenei/:idAteneo/:idScuola',
      //   component: AttivitaComponent,
      //   canActivate: [AuthGuard]
      // },
      {
        path: 'atenei/:idAteneo/:idScuola/sedi',
        component: SediComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'utenti',
        component: UtentiComponent,
        canActivate: [AuthGuard]
      },
      {
        path: 'utenti/:idUtente',
        component: UtenteComponent,
        canActivate: [AuthGuard]
      },
      {
        path: ':idScuola/dashboard',
        component: ScuolaDashboardComponent,
        canActivate: [AuthGuard]
      },
      {
        path: ':idScuola/unita-operative',
        component: UnitaOperativeScuolaComponent,
        canActivate: [AuthGuard]
      },
      {
        path: ':idScuola/turni',
        component: TurniComponent,
        canActivate: [AuthGuard]
      },
      {
        path: ':idScuola/utenti',
        children: [
          {
            path: '',
            component: UtentiScuolaComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idUtente',
            component: UtenteScuolaComponent,
            canActivate: [AuthGuard]
          }
        ]
      },
      {
        path: 'piano-di-studi',
        children: [
          {
            path: ':idScuola/coorti',
            component: CoortiComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte',
            component: ContatoriComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/tipologie',
            component: AttivitaTipologieComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/export',
            component: ExportComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/attivita',
            component: AttivitaComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/attivita/:idAttivita/filtri',
            component: AttivitaFiltriComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/attivita/:idAttivita/schema',
            component: AttivitaSchemaComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/filtri/:idContatore',
            component: AutonomiaFiltriComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/piano-studi',
            component: PianoStudiComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/coorti/:idCoorte/piano-studi/:idPianoStudi/insegnamenti',
            component: InsegnamentiComponent,
            canActivate: [AuthGuard]
          },
          {
            path: ':idScuola/obiettivi',
            component: ObiettiviComponent,
            canActivate: [AuthGuard]
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
  imports: [RouterModule.forRoot(routes, {onSameUrlNavigation: 'reload'})],
  exports: [RouterModule]
})
export class AppRoutingModule { }
