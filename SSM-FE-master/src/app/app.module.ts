// tslint:disable:max-line-length
import { BrowserModule } from '@angular/platform-browser';
import { NgModule, LOCALE_ID } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FlexLayoutModule } from '@angular/flex-layout';
import { AngularUtilsModule, InitConfig } from '@ottimis/angular-utils';
import { MainComponent } from './main/main.component';
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
import { MatMomentDateModule, MAT_MOMENT_DATE_ADAPTER_OPTIONS, MAT_MOMENT_DATE_FORMATS } from '@angular/material-moment-adapter';
import { MenuToggleModule } from './core/menu/menu-toggle.module';
import { MenuItems } from './core/menu/menu-items/menu-items';
import { SideBarComponent } from './Shared/side-bar/side-bar.component';
import { FooterComponent } from './Shared/footer/footer.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { PerfectScrollbarModule, PERFECT_SCROLLBAR_CONFIG, PerfectScrollbarConfigInterface } from 'ngx-perfect-scrollbar';
import { PageTitleService } from './core/page-title/page-title.service';
import { registerLocaleData } from '@angular/common';
import localeIt from '@angular/common/locales/it';
import { SearchService } from './core/search/search.service';
import { environment } from 'src/environments/environment';
import { ListAttivitaComponent } from './pages/attivita/list-attivita/list-attivita.component';
import { ListAttivitaNpComponent } from './pages/attivita/list-attivita-np/list-attivita-np.component';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { AgmCoreModule } from '@agm/core';
import { AttivitaComponent } from './pages/attivita/attivita/attivita.component';
import { OGCalendarModule } from './components/calendar/calendar.module';
import { AttivitaNpComponent } from './pages/attivita/attivita-np/attivita-np.component';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { ListStudentiComponent } from './pages/attivita/list-studenti/list-studenti.component';
import { ExportComponent } from './pages/attivita/export/export.component';
import { ListValutazioniComponent } from './pages/attivita/list-valutazioni/list-valutazioni.component';
import { SospensiveComponent } from './pages/utenti/sospensive/sospensive.component';
import { ListSurveyComponent } from './pages/survey/list/list.component';
import { ListJobTabelleComponent } from './pages/jobdescription/list/list.component';
import { JobColumnsComponent } from './pages/jobdescription/colonne/jobcolumns.component';
import { JobDatiComponent } from './pages/jobdescription/dati/dati.component';
import { DomandeSurveyComponent } from './pages/survey/domande/domande.component';
import { ListStudentiSurveyComponent } from './pages/survey/studenti/studenti.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { ChartsModule } from 'ng2-charts';
import { BarChartComponent } from './components/charts/bar-chart/bar-chart.component';
import { PieChartComponent } from './components/charts/pie-chart/pie-chart.component';
import { RisposteReportComponent } from './pages/survey/risposte-report/risposte-report.component';
import { CookieService } from 'ngx-cookie-service';
import { StandbyComponent } from './authentication/standby/standby.component';
import { TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { TranslateHttpLoader } from '@ngx-translate/http-loader';
import { HttpClient } from '@angular/common/http';
import { LanguageDropDownComponent } from './components/language-drop-down/language-drop-down.component';

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

export function HttpLoaderFactory(http: HttpClient) {
  return new TranslateHttpLoader(http);
}

@NgModule({
  declarations: [
    AppComponent,
    MainComponent,
    LoginComponent,
    SideBarComponent,
    FooterComponent,
    AttivitaComponent,
    ListAttivitaComponent,
    ListAttivitaNpComponent,
    AttivitaNpComponent,
    ListStudentiComponent,
    ExportComponent,
    ListValutazioniComponent,
    SospensiveComponent,
    ListSurveyComponent,
    DomandeSurveyComponent,
    ListStudentiSurveyComponent,
    ListJobTabelleComponent,
    JobColumnsComponent,
    JobDatiComponent,
    DashboardComponent,
    BarChartComponent,
    PieChartComponent,
    RisposteReportComponent,
    StandbyComponent,
    LanguageDropDownComponent
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    AngularUtilsModule.forRoot(config),
    TranslateModule.forRoot({
      defaultLanguage: 'it',
      loader: {
        provide: TranslateLoader,
        useFactory: HttpLoaderFactory,
        deps: [HttpClient]
      }
    }),
    FormsModule,
    OGCalendarModule,
    ReactiveFormsModule,
    AppRoutingModule,
    DragDropModule,
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
    MatProgressBarModule,
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
    ChartsModule,
    AgmCoreModule.forRoot({
      apiKey: '',
      libraries: ['places']
    }),
  ],
  providers: [
    MenuItems,
    PageTitleService,
    SearchService,
    CookieService,
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
