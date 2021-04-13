import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CalendarModule, DateAdapter } from 'angular-calendar';
import { adapterFactory } from 'angular-calendar/date-adapters/date-fns';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { OGCalendarComponent } from './calendar/calendar.component';
import { OGCalendarHeaderComponent } from './calendar-header/calendar-header.component';

@NgModule({
    declarations: [
        OGCalendarComponent,
        OGCalendarHeaderComponent
    ],
    imports: [
        CommonModule,
        CalendarModule.forRoot({
            provide: DateAdapter,
            useFactory: adapterFactory
        }),
        MatIconModule,
        MatCardModule,
        MatButtonModule
    ],
    exports : [
        OGCalendarComponent,
        OGCalendarHeaderComponent
    ]
})
export class OGCalendarModule { }
