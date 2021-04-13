import { Component, OnInit, ViewEncapsulation, ViewChild } from '@angular/core';
import { Rest, MainUtilsService, OGUploadComponent, Dialog } from '@ottimis/angular-utils';

@Component({
  selector: 'app-attivita-np',
  templateUrl: './attivita-np.component.html',
  styleUrls: ['./attivita-np.component.scss'],
  encapsulation: ViewEncapsulation.None
})
export class AttivitaNpComponent implements OnInit {

  path = 'specializzando_registrazioni_np';
  data: any = {
    dati_aggiuntivi: {}
  };
  settoriList: Array<{ id: string, nome: string }>;
  insegnamentiList: Array<{ id: string, nome: string }>;
  attivitaList: Array<{ id: string, nome_attivita: string }>;
  attivitaDati: Array<any>;

  @ViewChild('OGUpload') ogUpload: OGUploadComponent;
  upload: any = {
    uploadUrl: 'app_config/upload',
    additionalParameter: {
      id: 0
    }
  };

  constructor(
    private main: MainUtilsService,
    private dialog: Dialog
  ) { }

  ngOnInit(): void {
    this.getSettori();
    this.getAttivita();
  }

  onSubmit(data: any) {
    const obj: Rest = {
      type: 'PUT',
      path: `${this.path}/np`,
      body: this.data
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.data = res;
      }, (err) => {
        this.dialog.openConfirm('Attenzione', err.error, 'Ok');
    });
  }

  async getSettori() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/settori_scientifici`
    };
    this.settoriList = await this.main.rest(obj);
  }

  async getAttivita() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/attivita_np`
    };
    this.attivitaList = await this.main.rest(obj);
  }

  async getInsegnamenti(type: boolean)  {
    if (type) {
      return;
    }
    const obj: Rest = {
      type: 'POST',
      path: `${this.path}/insegnamenti`,
      body: {
        idpds: this.data.idpds
      }
    };
    this.insegnamentiList = await this.main.rest(obj);
  }

  async getAttivitaData(idAttivita: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/attivita_np/${idAttivita}`
    };
    this.attivitaDati = await this.main.rest(obj);
  }

}
