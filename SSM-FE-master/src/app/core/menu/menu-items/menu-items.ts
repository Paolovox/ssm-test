import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';

export interface ChildrenItems {
  state: string;
  name: string;
  type?: string;
}

export interface Menu {
  state: string;
  name: string;
  type: string;
  icon: string;
  idroles: Array<number>;
  children?: ChildrenItems[];
}

export enum MenuTypes {
  'STANDARD' = 0,
  'SCUOLE' = 1
}

let MENU: Array<Menu>;

@Injectable()
export class MenuItems {

  constructor(
    public translate: TranslateService
  ) {
    this.translate.get('MENU')
      .subscribe((res: any) => {
        MENU = [
          {
            state: 'dashboard',
            name: res.DASHBOARD,
            type: 'button',
            icon: 'view_quilt',
            idroles: [2, 9]
          },
          {
            state: 'specializzandi-list',
            name: res.LISTA_SPECIALIZZANDI,
            type: 'button',
            icon: 'school',
            idroles: [2, 7, 5, 9]
          },
          {
            state: 'attivita-list',
            name: res.LISTA_ATTIVITA,
            type: 'button',
            icon: 'account_balance',
            idroles: [8]
          },
          {
            state: 'attivita-list-np',
            name: res.ATTIVITA_NP,
            type: 'button',
            icon: 'account_balance',
            idroles: [8]
          },
          {
            state: 'export',
            name: res.CONTATORI,
            type: 'button',
            icon: 'av_timer',
            idroles: [8]
          },
          {
            state: 'valutazioni-list',
            name: res.VALUTAZIONI,
            type: 'button',
            icon: 'assignment',
            idroles: [8]
          },
          {
            state: 'survey',
            name: res.SURVEY,
            type: 'button',
            icon: 'assignment',
            idroles: [2, 9, 7, 8]
          },
          {
            state: 'jobtabelle',
            name: res.JOB_DESCRIPTION,
            type: 'button',
            icon: 'work_outline',
            idroles: [2, 9, 7, 8]
          },
          {
            state: 'utenti',
            name: res.UTENTI,
            type: 'menuScuole',
            icon: 'person',
            idroles: [9]
          }
        ]
      });
  }

  getAll() {
    return MENU;
  }
}
