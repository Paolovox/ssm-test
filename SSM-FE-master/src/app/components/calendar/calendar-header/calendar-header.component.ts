import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CalendarView } from 'angular-calendar';

@Component({
    // tslint:disable-next-line:component-selector
    selector: 'og-calendar-header',
    templateUrl: './calendar-header.component.html'
})
export class OGCalendarHeaderComponent {
    @Input() view: CalendarView | 'month' | 'week' | 'day';

    @Input() viewDate: Date;

    @Input() locale = 'it-IT';

    @Output() viewChange: EventEmitter<string> = new EventEmitter();

    @Output() viewDateChange: EventEmitter<Date> = new EventEmitter();
}
