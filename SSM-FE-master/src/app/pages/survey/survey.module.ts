import { NgModule } from '@angular/core';
import { CommonModule, registerLocaleData } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FlexLayoutModule } from '@angular/flex-layout';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { DomandeSurveyComponent } from './domande/domande.component';
import { ListSurveyComponent } from './list/list.component';
import { ListStudentiSurveyComponent } from './studenti/studenti.component';
import { SurveyRoutes } from './survey.routing';
import { MatSelectModule } from '@angular/material/select';
import { AngularUtilsModule, InitConfig } from '@ottimis/angular-utils';
import { environment } from 'src/environments/environment';
import { MatPaginatorIntl } from '@angular/material/paginator';
import localeIt from '@angular/common/locales/it';
import { MatInputModule } from '@angular/material/input';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';

const config: InitConfig = {
    url: environment.serverUrl,
    debug: true,
    restDefParams: []
};

// const rangeLabel = (page: number, pageSize: number, length: number) => {
//     if (length === 0 || pageSize === 0) { return `0 di ${length}`; }

//     length = Math.max(length, 0);

//     const startIndex = page * pageSize;

//     // If the start index exceeds the list length, do not try and fix the end index to the end.
//     const endIndex = startIndex < length ?
//         Math.min(startIndex + pageSize, length) :
//         startIndex + pageSize;

//     return `${startIndex + 1} - ${endIndex} di ${length}`;
// };

// export function paginatorText() {
//     const paginatorIntl = new MatPaginatorIntl();

//     paginatorIntl.itemsPerPageLabel = 'Elementi per pagina:';
//     paginatorIntl.getRangeLabel = rangeLabel;
//     paginatorIntl.firstPageLabel = 'Prima pagina';
//     paginatorIntl.lastPageLabel = 'Ultima pagina';
//     paginatorIntl.nextPageLabel = 'Pagina successiva';
//     paginatorIntl.previousPageLabel = 'Pagina precedente';

//     return paginatorIntl;
// }

@NgModule({
    declarations: [
        // DomandeSurveyComponent,
        // ListSurveyComponent,
        // ListStudentiSurveyComponent
    ],
    imports: [
        CommonModule,
        FlexLayoutModule,
        FormsModule,
        ReactiveFormsModule,
        RouterModule.forChild(SurveyRoutes),
        MatSelectModule,
        MatInputModule,
        MatCardModule,
        MatDividerModule,
        MatIconModule,
        MatButtonModule,
        // AngularUtilsModule
    ]
})
export class SurveyModule { }
