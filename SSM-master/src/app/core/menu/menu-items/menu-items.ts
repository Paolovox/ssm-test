import { Injectable } from '@angular/core';
import { MainUtilsService } from '@ottimis/angular-utils';

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

const MENU = [
  [
    {
      state: 'atenei',
      name: 'Atenei',
      type: 'button',
      icon: 'account_balance',
      idroles: [1, 2]
    },
    {
      state: 'aziende',
      name: 'Aziende',
      type: 'button',
      icon: 'local_hospital',
      idroles: [1]
    },
    {
      state: 'presidi',
      name: 'Presidi',
      type: 'button',
      icon: 'apartment',
      idroles: [1]
    },
    {
      state: 'unita-operative',
      name: 'Unità operative',
      type: 'button',
      icon: 'airline_seat_individual_suite',
      idroles: [1]
    },
    {
      state: 'scuole',
      name: 'Scuole di specializzazione',
      type: 'button',
      icon: 'view_list',
      idroles: [1]
    },
    {
      state: 'pds',
      name: 'Piano di studi',
      type: 'sub',
      icon: 'menu_book',
      idroles: [1],
      children: [
        {
          state: 'aree',
          name: 'Aree',
          type: 'link'
        },
        {
          state: 'classi',
          name: 'Classi',
          type: 'link'
        },
        {
          state: 'settori-scientifici',
          name: 'Settori scientifici',
          type: 'link'
        },
        {
          state: 'attivita-formative',
          name: 'Attività formative',
          type: 'link'
        },
        {
          state: 'ambiti-disciplinari',
          name: 'Ambiti disciplinari',
          type: 'link'
        }
      ]
    },
    {
      state: 'utenti',
      name: 'Utenti',
      type: 'button',
      icon: 'person',
      idroles: [1]
    },
    {
      state: 'copy',
      name: 'Copia',
      type: 'button',
      icon: 'file_copy',
      idroles: [1]
    },
    {
      state: 'import',
      name: 'Import',
      icon: 'account_balance',
      type: 'sub',
      idroles: [1],
      children: [
        {
          state: 'associazione-scuole',
          name: 'Associazione scuole',
          type: 'button'
        },
        {
          state: 'associazione-aziende',
          name: 'Associazione aziende',
          type: 'button'
        },
        {
          state: 'associazione-presidi',
          name: 'Associazione presidi',
          type: 'button'
        },
        {
          state: 'associazione-unita',
          name: 'Associazione unità',
          type: 'button'
        }
      ]
    }
  ],
  [
    {
      state: 'unita-operative',
      name: 'Unità operative',
      type: 'menuScuole',
      icon: 'airline_seat_individual_suite',
      idroles: [1, 2, 9]
    },
    {
      state: 'turni',
      name: 'Turni',
      type: 'menuScuole',
      icon: 'schedule',
      idroles: [1, 2, 9]
    },
    {
      state: 'attivita',
      name: 'Attività',
      type: 'sub',
      icon: 'medical_services',
      idroles: [1, 2, 9],
      children: [
        {
          state: 'prestazioni',
          name: 'Lista prestazioni',
          type: 'link'
        },
        {
          state: 'combo',
          name: 'Combo attività',
          type: 'link'
        },
        {
          state: 'np',
          name: 'Attività non professionalizzanti',
          type: 'link'
        }
      ]
    },
    {
      state: 'piano-di-studi',
      name: 'Piano di studi',
      type: 'sub',
      icon: 'school',
      // label: 'New',
      idroles: [1, 2, 9],
      children: [
        {
          state: 'coorti',
          name: 'Coorti',
          type: 'menuScuole'
        },
        {
          state: 'obiettivi',
          name: 'Obiettivi',
          type: 'menuScuole'
        }
      ]
    },
    {
      state: 'utenti',
      name: 'Utenti',
      type: 'menuScuole',
      icon: 'person',
      idroles: [1, 2]
    }
  ]
];

@Injectable()
export class MenuItems {

  private type: MenuTypes = MenuTypes.STANDARD;

  constructor(
    private main: MainUtilsService
  ) {
    if (this.main.getUserData('idScuola')) {
      this.type = MenuTypes.SCUOLE;
    }
  }

  getAll(): Menu[] {
    return MENU[this.type];
  }

  getType() {
    return this.type;
  }

  switchMenu(type: MenuTypes)  {
    this.type = type;
  }

  add(menu: any, type: MenuTypes) {
    MENU[type].push(menu);
  }
}
