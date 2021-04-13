import { Component, OnInit, ViewChild, ViewEncapsulation, ChangeDetectorRef } from '@angular/core';
import { MainUtilsService, Dialog, Rest, OGUploadComponent } from '@ottimis/angular-utils';
import { ActivatedRoute, Router } from '@angular/router';
import { fadeInAnimation } from '../../../core/route-animation/route.animation';
import * as moment from 'moment';
import { TranslateService } from '@ngx-translate/core';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-attivita-np',
  templateUrl: './attivita-np.component.html',
  styleUrls: ['./attivita-np.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation],
  encapsulation: ViewEncapsulation.None
})
export class AttivitaNpComponent implements OnInit {

  path = 'specializzando_registrazioni_np';
  idAttivita: string;
  data: any = {
    dati_aggiuntivi: {}
  };
  settoriList: Array<{ id: string, nome: string }>;
  insegnamentiList: Array<{ id: string, nome: string }>;
  attivitaList: Array<{ id: string, nome_attivita: string }>;
  attivitaDati: Array<any>;

  url = environment.serverUrl;

  calendar = false;

  @ViewChild('OGUpload') ogUpload: OGUploadComponent;
  upload: any = {
    uploadUrl: 'upload',
    deleteUrl: 'upload'
  };

  idSpecializzando: string;
  translated: any = {};

  constructor(
    public main: MainUtilsService,
    private dialog: Dialog,
    private aRoute: ActivatedRoute,
    private router: Router,
    public translate: TranslateService
  ) {
    this.translate.get('ATTIVITA_NP')
      .subscribe((res: any) => {
        this.translated = res;
      });
    this.idAttivita = this.aRoute.snapshot.paramMap.get('idAttivita');
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idSpecializzando = params.get('idSpecializzando');
    });
  }

  ngOnInit(): void {
    this.getAttivitaAll();
  }

  getAttivitaAll()  {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idAttivita}`,
      queryParams: {}
    };
    if (this.idSpecializzando)  {
      obj.queryParams.idspecializzando = this.idSpecializzando;
    }
    this.main.rest(obj)
      .then((res: any) => {
        if (res.settori_scientifici_list) {
          this.settoriList = res.settori_scientifici_list;
          delete (res.settori_scientifici_list);
        }
        if (res.insegnamenti_list) {
          this.insegnamentiList = res.insegnamenti_list;
          delete (res.insegnamenti_list);
        }
        if (res.attivita_list) {
          this.attivitaList = res.attivita_list;
          delete (res.attivita_list);
        }
        if (res.dati_aggiuntivi_list) {
          this.attivitaDati = res.dati_aggiuntivi_list;
          delete (res.dati_aggiuntivi_list);
        }
        this.data = res;
        if (!this.data.dati_aggiuntivi) {
          this.data.dati_aggiuntivi = {};
        }
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.OK);
    });
  }

  onSubmit() {
    const obj: Rest = {
      type: this.idAttivita !== '0' ? 'POST' : 'PUT',
      path: `${this.path}/np`,
      body: this.data
    };
    if (this.idAttivita !== '0') {
      obj.path = `${this.path}/np/${this.idAttivita}`;
    }
    this.main.rest(obj)
      .then((res: any) => {
        this.router.navigate(['attivita-list-np']);
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.OK);
      });
  }

  async getInsegnamenti(type: boolean) {
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
    const datiAggiuntivi = await this.main.rest(obj);
    this.calendar = datiAggiuntivi.calendar;
    const upload = datiAggiuntivi.list.find((e) => e.idtipo_campo == 2);
    this.attivitaDati = datiAggiuntivi.list;
    setTimeout(() => {
      if (upload) {
        if (!this.data.attach)  {
          this.data.attach = [];
        }
        this.ogUpload?.initUpload()
          .subscribe((res) => {
            this.data.attach.push(res.data.res);
          });
      }
    }, 200);
  }

  deleteAttach(index: number) {
    this.dialog.openConfirm(this.translated.ATTENZIONE, this.translated.ELIMINARE_FILE, this.translated.SI, this.translated.ANNULLA)
      .then(() => {
        this.data.attach.splice(index, 1);
      }, () => {
      });
  }

  isSpec() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 8) {
      return true;
    }
  }

}
