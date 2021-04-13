// tslint:disable:max-line-length
import { BrowserModule } from '@angular/platform-browser';
import { NgModule, LOCALE_ID } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FlexLayoutModule } from '@angular/flex-layout';
import { AngularUtilsModule, InitConfig } from '@ottimis/angular-utils';
import { MainComponent } from './main/main.component';
import { AteneiComponent } from './pages/atenei/atenei/atenei.component';
import { LoginComponent } from './authentication/login/login.component';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatBadgeModule } from '@angular/material/badge';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatChipsModule } from '@angular/material/chips';
import { MAT_DATE_LOCALE, MAT_DATE_FORMATS, MatRippleModule } from '@angular/material/core';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatPaginatorModule, MatPaginatorIntl } from '@angular/material/paginator';
import { MatSelectModule } from '@angular/material/select';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatSortModule } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatGoogleMapsAutocompleteModule } from '@angular-material-extensions/google-maps-autocomplete';
import { MatMomentDateModule, MAT_MOMENT_DATE_ADAPTER_OPTIONS, MAT_MOMENT_DATE_FORMATS } from '@angular/material-moment-adapter';
import { MenuToggleModule } from './core/menu/menu-toggle.module';
import { MenuItems } from './core/menu/menu-items/menu-items';
import { SideBarComponent } from './Shared/side-bar/side-bar.component';
import { BreadcrumbService, Ng5BreadcrumbModule } from 'ng5-breadcrumb';
import { FooterComponent } from './Shared/footer/footer.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { PerfectScrollbarModule, PERFECT_SCROLLBAR_CONFIG, PerfectScrollbarConfigInterface } from 'ngx-perfect-scrollbar';
import { PageTitleService } from './core/page-title/page-title.service';
import { registerLocaleData } from '@angular/common';
import localeIt from '@angular/common/locales/it';
import { SearchService } from './core/search/search.service';
import { environment } from 'src/environments/environment';
import { UtentiComponent } from './pages/utenti/utenti/utenti.component';
import { ScuoleDiSpecializzazioneComponent } from './pages/atenei/scuole-di-specializzazione/scuole-di-specializzazione.component';
import { SediComponent } from './pages/sedi/sedi.component';
import { ScuoleComponent } from './pages/scuole/scuole.component';
import { ScuolaDashboardComponent } from './pages/scuola/scuola-dashboard/scuola-dashboard.component';
import { SettoriScientificiComponent } from './pages/pds/settori-scientifici/settori-scientifici.component';
import { AmbitiDisciplinariComponent } from './pages/pds/ambiti-disciplinari/ambiti-disciplinari.component';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { AziendeComponent } from './pages/aziende/aziende.component';
import { PresidiComponent } from './pages/presidi/presidi.component';
import { UnitaOperativeComponent } from './pages/unita-operative/unita-operative.component';
import { AgmCoreModule } from '@agm/core';
import { UnitaOperativeScuolaComponent } from './pages/scuola/unita-operative-scuola/unita-operative-scuola.component';
import { UtenteComponent } from './pages/utenti/utente/utente.component';
import { UtenteScuolaComponent } from './pages/scuola/utenti/utente/utente-scuola.component';
import { UtentiScuolaComponent } from './pages/scuola/utenti/utenti/utenti-scuola.component';
import { OGModalListComponent } from './components/ogmodal-list/ogmodal-list.component';
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

registerLocaleData(localeIt, 'it-IT');

const DEFAULT_PERFECT_SCROLLBAR_CONFIG: PerfectScrollbarConfigInterface = {
  suppressScrollX: true
};

const config: InitConfig = {
  url: environment.serverUrl,
  debug: true,
  restDefParams: []
};

const rangeLabel = (page: number, pageSize: number, length: number) => {
  if (length === 0 || pageSize === 0) { return `0 di ${length}`; }

  length = Math.max(length, 0);

  const startIndex = page * pageSize;

  // If the start index exceeds the list length, do not try and fix the end index to the end.
  const endIndex = startIndex < length ?
    Math.min(startIndex + pageSize, length) :
    startIndex + pageSize;

  return `${startIndex + 1} - ${endIndex} di ${length}`;
};

export function paginatorText() {
  const paginatorIntl = new MatPaginatorIntl();

  paginatorIntl.itemsPerPageLabel = 'Elementi per pagina:';
  paginatorIntl.getRangeLabel = rangeLabel;
  paginatorIntl.firstPageLabel = 'Prima pagina';
  paginatorIntl.lastPageLabel = 'Ultima pagina';
  paginatorIntl.nextPageLabel = 'Pagina successiva';
  paginatorIntl.previousPageLabel = 'Pagina precedente';

  return paginatorIntl;
}

@NgModule({
  declarations: [
    AppComponent,
    MainComponent,
    AssociazioneComponent,
    AssociazionePresidiComponent,
    AssociazioneUnitaComponent,
    AssociazioneScuoleComponent,
    AteneiComponent,
    AziendeComponent,
    ScuoleComponent,
    PresidiComponent,
    UnitaOperativeComponent,
    LoginComponent,
    SideBarComponent,
    FooterComponent,
    ScuoleDiSpecializzazioneComponent,
    SettoriScientificiComponent,
    ClassiComponent,
    AreeComponent,
    AttivitaFormativeComponent,
    AmbitiDisciplinariComponent,
    AttivitaComponent,
    AttivitaTipologieComponent,
    AttivitaComboComponent,
    AttivitaSchemaComponent,
    AttivitaFiltriComponent,
    AutonomiaFiltriComponent,
    PrestazioniComponent,
    TurniComponent,
    UtentiComponent,
    UtenteComponent,
    SediComponent,
    OGModalListComponent,
    ScuolaDashboardComponent,
    UnitaOperativeScuolaComponent,
    ObiettiviComponent,
    PianoStudiComponent,
    InsegnamentiComponent,
    CoortiComponent,
    ContatoriComponent,
    ExportComponent,
    UtenteScuolaComponent,
    UtentiScuolaComponent,
    AttivitaNpComponent,
    AttivitaNpDatiAggiuntiviComponent,
    AttivitaNpCalendarComponent,
    CopyComponent,
    StandbyComponent
  ],
  imports: [
    BrowserModule,
    FormsModule,
    ReactiveFormsModule,
    AppRoutingModule,
    DragDropModule,
    BrowserAnimationsModule,
    PerfectScrollbarModule,
    FlexLayoutModule,
    MenuToggleModule,
    MatSelectModule,
    MatSortModule,
    MatSidenavModule,
    MatIconModule,
    MatMenuModule,
    MatBadgeModule,
    MatToolbarModule,
    MatTooltipModule,
    MatListModule,
    MatCheckboxModule,
    MatCardModule,
    MatTabsModule,
    MatSlideToggleModule,
    MatPaginatorModule,
    MatTableModule,
    MatChipsModule,
    MatInputModule,
    MatButtonModule,
    MatAutocompleteModule,
    MatRippleModule,
    MatDatepickerModule,
    MatMomentDateModule,
    MatGoogleMapsAutocompleteModule,
    AgmCoreModule.forRoot({
      apiKey: '',
      libraries: ['places']
    }),
    AngularUtilsModule.forRoot(config),
    Ng5BreadcrumbModule.forRoot()
  ],
  providers: [
    MenuItems,
    BreadcrumbService,
    PageTitleService,
    SearchService,
    {
      provide: PERFECT_SCROLLBAR_CONFIG,
      useValue: DEFAULT_PERFECT_SCROLLBAR_CONFIG
    },
    {
      provide: LOCALE_ID,
      useValue: 'it-IT'
    },
    { provide: MAT_DATE_LOCALE, useValue: 'it-IT' },
    { provide: MAT_DATE_FORMATS, useValue: MAT_MOMENT_DATE_FORMATS },
    { provide: MAT_MOMENT_DATE_ADAPTER_OPTIONS, useValue: { useUtc: true } },
    { provide: MatPaginatorIntl, useValue: paginatorText() }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
