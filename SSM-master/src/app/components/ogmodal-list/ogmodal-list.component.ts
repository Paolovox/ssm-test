import { Component, OnInit, Input, ViewEncapsulation, ElementRef } from '@angular/core';
import { trigger, transition, style, animate, state } from '@angular/animations';
import { Observable, Subscriber } from 'rxjs';

export interface DialogListResponse {
  event: DialogListEvents;
  data?: string | DialogListItem;
}

export interface DialogListItem {
  id: any;
  nome: string;
  helpName: string;
  edit?: boolean;
}

export enum DialogListEvents {
  ADD = 'add',
  EDIT = 'edit',
  DELETE = 'delete',
}


@Component({
  // tslint:disable-next-line:component-selector
  selector: 'og-modal-list',
  templateUrl: './ogmodal-list.component.html',
  styleUrls: ['./ogmodal-list.component.scss'],
  encapsulation: ViewEncapsulation.None,
  animations: [
    trigger('fade', [
      state('in', style({ opacity: 1 })),
      transition(':enter', [
        style({ opacity: 0 }),
        animate(200)
      ]),
      transition(':leave',
        animate(200, style({ opacity: 0 })))
    ])
  ]
})
export class OGModalListComponent implements OnInit {

  showModal = false;
  title = '';
  subTitle = '';

  @Input()
  listItems = Array<DialogListItem>();

  element: any;
  newElement: string;
  modalObserver: Subscriber<any>;

  constructor(
    private el: ElementRef
  ) {
    this.element = this.el.nativeElement;
  }

  ngOnInit() {
  }

  openModal(title: string, subTitle = ''): Observable<DialogListResponse> {
    this.title = title;
    this.subTitle = subTitle;
    return new Observable((observer) => {
      document.body.appendChild(this.element);
      this.modalObserver = observer;
      this.showModal = true;
    });
  }

  changeEvent(type: string, event: Event, elementType: string) {
    this.modalObserver.next({ event: elementType, type, data: event });
  }

  close() {
    this.showModal = false;
    this.element.remove();
  }

  addEvent(e: any) {
    if (e.key === 'Enter') {
      const obj: DialogListResponse = {
        event: DialogListEvents.ADD,
        data: e.target.value.toString()
      };
      this.modalObserver.next(obj);
      this.newElement = '';
    }
  }

  editEvent(item: DialogListItem) {
    const obj: DialogListResponse = {
      event: DialogListEvents.EDIT,
      data: item
    };
    this.modalObserver.next(obj);
  }

  deleteEvent(item: DialogListItem) {
    const obj: DialogListResponse = {
      event: DialogListEvents.DELETE,
      data: item
    };
    this.modalObserver.next(obj);
  }

}
