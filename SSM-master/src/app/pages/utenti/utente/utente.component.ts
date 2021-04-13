import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MainUtilsService, Rest, OGModalComponent, Dialog, OGListSettings, OGListStyleType, OGListComponent } from '@ottimis/angular-utils';
import { trigger, state, style, transition, animate } from '@angular/animations';
import { Observable } from 'rxjs';
// import { OGModalComponent } from 'src/app/components/ogmodal/ogmodal.component';

@Component({
  selector: 'app-utente',
  templateUrl: './utente.component.html',
  styleUrls: ['./utente.component.scss'],
  animations: [
    trigger('slideLeftRight', [
      state('in', style({
        width: 'auto',
        display: 'block'
      })),
      state('out', style({
        width: '0px',
        display: 'none'
      })),
      transition('in => out', animate('400ms ease-in-out')),
      transition('out => in', animate('400ms ease-in-out'))
    ])
  ]
})
export class UtenteComponent implements OnInit {

  path = 'users';

  readonly = true;

  @ViewChild('OGModal') ogModal: OGModalComponent;
  @ViewChild('rolesTable') rolesTable: OGListComponent;

  selectOptions = {
    ruoli: Array<{ id: string, text: string }>(),
    atenei: Array<{ id: string, text: string }>(),
    settori_scientifici: Array<{ id: string, text: string }>(),
    scuole: Array<{ id: string, text: string }>(),
    presidi: Array<{ id: string, text: string }>(),
    unita: Array<{ id: string, text: string }>(),
    coorti: Array<{ id: string, text: string }>(),
    anni: Array<{ id: string, text: string }>(),
  };
  dialogFields = [
    {
      type: 'SELECT',
      selectOptions: 'ruoli',
      placeholder: 'Ruolo',
      name: 'idruolo',
      col: '30'
    },
    {
      type: 'SELECT',
      selectOptions: 'atenei',
      placeholder: 'Ateneo',
      name: 'idateneo',
      visible: () => false,
      col: '50'
    },
    {
      type: 'SELECT',
      selectOptions: 'settori_scientifici',
      placeholder: 'Settori scientifici',
      name: 'idsettore_scientifico',
      visible: () => false,
      selectMultiple: true
    },
    {
      type: 'SELECT',
      selectOptions: 'scuole',
      placeholder: 'Scuola',
      name: 'idscuola',
      visible: () => false
    },
    {
      type: 'SELECT',
      selectOptions: 'coorti',
      placeholder: 'Coorte',
      name: 'idcoorte',
      visible: () => false
    },
    {
      type: 'SELECT',
      selectOptions: 'anni',
      placeholder: 'Anno',
      name: 'anno',
      visible: () => false
    },
    {
      type: 'SELECT',
      selectOptions: 'presidi',
      placeholder: 'Presidio',
      name: 'idpresidio',
      visible: () => false
    },
    {
      type: 'SELECT',
      selectOptions: 'unita',
      placeholder: 'Unità operativa',
      name: 'idunita',
      visible: () => false
    }
  ];

  role: any = {};
  roles: Array<any>;

  settings: OGListSettings = {
    columns: [
      {
        column: 'nome',
        name: 'Ruolo',
        style: OGListStyleType.BOLD
      },
      {
        column: 'testo',
        name: 'Sezioni',
        style: OGListStyleType.NORMAL
      }
    ],
    actionColumns: {
      edit: false
    },
    pagingData: {
      total: 0,
      page: 1,
      order: 'asc',
      sort: 'nome',
      pageSize: 20
    },
    search: '',
    selection: []
  };

  idUser: string;

  data: any = {};
  saved = 'out';

  constructor(
    private aRoute: ActivatedRoute,
    private main: MainUtilsService,
    private dialog: Dialog
  ) { }

  ngOnInit() {
    this.idUser = this.aRoute.snapshot.paramMap.get('idUtente');
    this.getUser();
  }

  getUser() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idUser}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res;
        this.selectOptions.ruoli = res.ruoli_list;
        this.roles = res.ruoli;
      });
  }

  operations(e) {
    switch (e.type) {
      case 'delete':
        this.deleteRole(e.element.id, e.element.nome);
        break;
      default:
        break;
    }
  }

  deleteRole(id: string, ruolo: string) {
    this.dialog.openConfirm('Elimina ruolo', 'Sei sicuro di voler eliminare il ruolo ' + ruolo + '?', 'ELIMINA', 'Annulla')
    .then(() => {
      const obj: Rest = {
        type: 'DELETE',
        path: `${this.path}/ruolo/${this.idUser}/${id}`
      };
      this.main.rest(obj)
        .then((res: any) => {
          this.getUser();
        }, (err) => {
          this.dialog.openConfirm('Attenzione', err.error, 'Chiudi');
        });
    });
  }

  addRuolo()  {
    this.ruoloModal()
    .subscribe((res) => {
      const obj: Rest = {
        type: 'PUT',
        path: `${this.path}/ruolo/${this.idUser}`,
        body: res
      };
      this.main.rest(obj)
        .then(() => {
          this.selectOptions = {
            ruoli: Array<{ id: string, text: string }>(),
            atenei: Array<{ id: string, text: string }>(),
            scuole: Array<{ id: string, text: string }>(),
            presidi: Array<{ id: string, text: string }>(),
            unita: Array<{ id: string, text: string }>(),
            settori_scientifici: Array<{ id: string, text: string }>(),
            coorti: Array<{ id: string, text: string }>(),
            anni: Array<{ id: string, text: string }>(),
          };
          this.getUser();
        }, (err) => {
      });
    });
  }

  ruoloModal()  {
    return new Observable((observer) => {
      this.ogModal.openModal('Ruolo utente', 'Seleziona un ruolo per l\'utente')
        .subscribe((res: any) => {
          if (res.event === 'confirm') {
            observer.next(res.data);
            observer.complete();
          } else if (res.event === 'selectionChange') {
            this.resetSelect(res.type);
            this.role[res.type] = res.data.value;
            this.getRoleData();
          }
        }, (err) => {
          observer.complete();
        });
      });
  }

  getRoleData() {
    const obj: Rest = {
      type: 'GET',
      path: `users/ruolo/${this.idUser}`,
      queryParams: this.role
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (res.data) {
          this.selectOptions[res.nome_list] = res.data;
          this.dialogFields = this.dialogFields.map((e) => {
            if (this.selectOptions[e.selectOptions].length > 0)  {
              e.visible = () => true;
            } else {
              e.visible = () => false;
            }
            return e;
          });
        }
      }, (err) => {
    });
  }

  resetSelect(nomeList: string)  {
    switch (nomeList) {
      case 'idruolo':
        this.selectOptions.atenei = new Array();
        delete (this.role.idateneo);
        this.selectOptions.scuole = new Array();
        delete (this.role.scuola);
        this.selectOptions.presidi = new Array();
        delete (this.role.idpresidio);
        this.selectOptions.unita = new Array();
        delete (this.role.idunita);
        this.selectOptions.coorti = new Array();
        delete (this.role.idcoorte);
        this.selectOptions.anni = new Array();
        delete (this.role.anno);
        break;
      case 'idateneo':
        this.selectOptions.scuole = new Array();
        delete (this.role.idscuola);
        this.selectOptions.settori_scientifici = new Array();
        delete (this.role.idsettore_scientifico);
        this.selectOptions.coorti = new Array();
        delete (this.role.idcoorte);
        this.selectOptions.anni = new Array();
        delete (this.role.anno);
        break;
      case 'idscuola':
        this.selectOptions.coorti = new Array();
        delete (this.role.idcoorte);
        break;
      case 'idcoorte':
        this.selectOptions.anni = new Array();
        delete (this.role.anno);
        break;
      case 'idpresidio':
        this.selectOptions.unita = new Array();
        delete (this.role.idunita);
        break;
      default:
        break;
    }
  }

}
