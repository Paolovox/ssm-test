import { Component, OnInit , ViewEncapsulation, Input } from '@angular/core';
import { Subject } from 'rxjs';
import { CalendarEvent,
         CalendarMonthViewDay
      } from 'angular-calendar';
import { WeekViewHour } from 'calendar-utils';
import * as moment from 'moment';

@Component({
   // tslint:disable-next-line:component-selector
   selector: 'og-calendar',
   templateUrl: './calendar-material.html',
   styleUrls: ['./calendar-material.scss'],
   encapsulation: ViewEncapsulation.None
})

export class OGCalendarComponent implements OnInit {

   activeDayIsOpen = true;

   view = 'month';
   viewDate: Date = new Date();

   selectedMonthViewDay: CalendarMonthViewDay;

   selectedDayViewDate: Date;

   dayView: WeekViewHour[];

   events: CalendarEvent[] = [];

   selectedDays: Array<any>;

   @Input()
   set selectedDaysAr(selectedDays: Array<any>) {
      this.selectedDays = selectedDays;
   }
   get selectedDaysAr(): Array<any> {
      return this.selectedDays;
   }

   modalData: {
      action: string,
      event: CalendarEvent
   };

   refresh: Subject<any> = new Subject();


   constructor() {}

   ngOnInit() {
      moment.locale('it-IT');
   }

   dayClicked(day: CalendarMonthViewDay): void {
      this.selectedMonthViewDay = day;
      const selectedDateTime = this.selectedMonthViewDay.date.getTime();
      const dateIndex = this.selectedDays.findIndex(
         selectedDay => selectedDay.date.getTime() === selectedDateTime
      );
      if (dateIndex > -1) {
         this.selectedMonthViewDay.badgeTotal = 0;
         delete this.selectedMonthViewDay.cssClass;
         this.selectedDays.splice(dateIndex, 1);
      } else {
         this.selectedDays.push(this.selectedMonthViewDay);
         day.cssClass = 'cal-day-selected';
         day.badgeTotal = 1;
         this.selectedMonthViewDay = day;
      }
      this.selectedDays.map(e => {
         e.text_date = moment(e.date).format('YYYY-MM-DD');
         e.human_date = moment(e.date).format('DD MMMM YYYY');
         return e;
      });
      this.selectedDays.sort((a, b) => {
         return moment(a.date).diff(moment(b.date));
      });
   }

   dayUncheck(index: number) {
      this.selectedDays[index].badgeTotal = 0;
      delete this.selectedDays[index].cssClass;
      this.selectedDays.splice(index, 1);
   }

   beforeMonthViewRender({ body }: { body: CalendarMonthViewDay[] }): void {
      body.forEach(day => {
         if (
            this.selectedDays.some(
               selectedDay => selectedDay.date.getTime() === day.date.getTime()
            )
         ) {
            day.cssClass = 'cal-day-selected';
         }
      });
   }

   hourSegmentClicked(date: Date) {
      this.selectedDayViewDate = date;
      this.addSelectedDayViewClass();
   }

   beforeDayViewRender(dayView: WeekViewHour[]) {
      this.dayView = dayView;
      this.addSelectedDayViewClass();
   }

   private addSelectedDayViewClass() {
      this.dayView.forEach(hourSegment => {
         hourSegment.segments.forEach(segment => {
            delete segment.cssClass;
            if (
               this.selectedDayViewDate &&
               segment.date.getTime() === this.selectedDayViewDate.getTime()
            ) {
               segment.cssClass = 'cal-day-selected';
            }
         });
      });
   }

   dateIsValid(date: Date): boolean {
      return date.getTime() < new Date().getTime();
   }

   applyDateSelectionPolicy({ body }: { body: CalendarMonthViewDay[] }): void {
      body.forEach(day => {
         if (!this.dateIsValid(day.date)) {
            day.cssClass = 'disabled-date';
         }
      });
   }
}
