import { Component, OnInit, ViewChild, OnDestroy, AfterViewInit } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, DialogFields, OGModalComponent, OGUploadComponent } from '@ottimis/angular-utils';
import { filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { fadeInAnimation } from '../../../core/route-animation/route.animation';
import * as moment from 'moment';
import { TranslateService } from '@ngx-translate/core';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-attivita',
  templateUrl: './attivita.component.html',
  styleUrls: ['./attivita.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation]
})
export class AttivitaComponent implements OnInit, OnDestroy, AfterViewInit {

  moment = moment;

  path = 'specializzando_registrazioni';
  data: any = {};
  idScuola: string;
  idAttivita: string;

  comboSelectedAll = false;
  noAutonomia = false;

  additionalFields: any = {};

  url = environment.serverUrl;

  unitaList = Array<{ id: string, text: string }>();
  tutorList = Array<{ id: string, text: string }>();
  tipologieList = [
    {id: 1, text: 'Prestazione'},
    {id: 2, text: 'Frequenza'}
  ];
  prestazioniList = Array<{ id: string, text: string }>();
  attivitaList = Array<{ id: string, text: string }>();
  autonomiaList = Array<{ id: string, text: string }>();

  @ViewChild('OGModal') ogModal: OGModalComponent;
  search$: Subscription;
  router$: Subscription;

  selectOptions: any = {
    attivita_list: Array<{ id: string, text: string }>(),
    prestazioni_list: Array<{ id: string, text: string }>()
  };
  dialogFields: Array<DialogFields> = [
  ];

  uploadObserver: any;
  @ViewChild('OGUpload') ogUpload: OGUploadComponent;
  upload: any = {
    uploadUrl: 'upload',
    deleteUrl: 'upload'
  };

  idSpecializzando: string;
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    public main: MainUtilsService,
    private dialog: Dialog,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('ATTIVITA')
      .subscribe((res: any) => {
        this.translated = res;
        this.dialogFields = [
          {
            type: 'SELECT',
            placeholder: res.PRESTAZIONE,
            name: 'idprestazione',
            selectOptions: 'prestazioni_list',
            selectMultiple: true
          },
          {
            type: 'SELECT',
            placeholder: res.ATTIVITA,
            name: 'idattivita',
            selectOptions: 'attivita_list'
          }
        ]
      });
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idSpecializzando = params.get('idSpecializzando');
    });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.idAttivita = this.aRoute.snapshot.paramMap.get('idAttivita');
    this.pageTitleService.setTitle(this.translated.NUOVA_ATTIVITA, '');
    this.data.selectedDays = [];
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getData();
    });
  }

  ngAfterViewInit(): void {
    this.getData();
  }

  ngOnDestroy() {
    this.router$.unsubscribe();
  }

  getData() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/${this.idAttivita}`,
      queryParams: {}
    };
    if (this.idSpecializzando) {
      obj.queryParams.idspecializzando = this.idSpecializzando;
    }
    this.main.rest(obj)
      .then((res: any) => {
        if (res.data) {
          if (res.additional_fields) {
            this.setAdditionalFields(0, res.additional_fields);
          }
          this.data = res.data;
          if (res.data.struttura) {
            this.data.struttura = JSON.parse(res.data.struttura);
          }
        }
        if (res.unita_list) {
          this.unitaList = res.unita_list;
        }
        if (res.tutor_list) {
          this.tutorList = res.tutor_list;
        }
        if (res.prestazioni_list) {
          this.prestazioniList = res.prestazioni_list;
        }
        if (res.attivita_list) {
          this.attivitaList = res.attivita_list;
        }
        if (!this.data.attach)  {
          this.data.attach = [];
        }
        if (res.autonomiaList) {
          this.comboSelectedAll = true;
          this.autonomiaList = res.autonomiaList;
        }
      });
  }

  getTutor(id: string)  {
    delete(this.data.idtutor);
    delete(this.data.direttore);
    delete(this.data.idattivita);
    delete(this.data.idprestazione);
    this.tutorList = [];
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/unita_info/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (res && res.tutor_list)  {
          this.tutorList = res.tutor_list;
        }
        this.data.direttore = res.direttore;
      }, (err) => {
    });
  }

  getAttivita() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/attivita/list`
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.attivitaList = res.attivita_list;
      });
  }

  getPrestazioni(id: string) {
    delete(this.data.idprestazione);
    delete (this.data.idautonomia);
    delete(this.data.struttura);
    this.setAdditionalFields(id);
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/attivita/list/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (res && res.prestazioni_list)  {
          this.prestazioniList = res.prestazioni_list;
        }
      });
  }

  setAdditionalFields(idAttivita: string | number, additionalFields?: any) {
    if (additionalFields) {
      this.additionalFields = additionalFields;
    } else {
      this.additionalFields = this.attivitaList.filter(e => e.id === idAttivita)[0];
    }
    if (this.additionalFields.opzione_upload === '1') {
      this.uploadObserver = this.ogUpload?.initUpload()
        .subscribe((res) => {
          this.data.attach.push(res.data.res);
        });
    } else {
      if (this.uploadObserver) {
        this.uploadObserver.unsubscribe();
      }
    }
  }

  getCombos(id: string) {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/combo/${this.data.idattivita}/${id}`
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (res.combo_list) {
          this.data.struttura = res.combo_list;
        } else {
          this.noAutonomia = true;
          this.comboSelectedAll = true;
          this.data.struttura = true;
          if (res.autonomia_calc) {
            this.noAutonomia = false;
            this.getAuotonomia();
          }
        }
      });
  }

  comboSelected() {
    delete(this.data.idautonomia);
    this.comboSelectedAll = this.data.struttura.every((e) => {
      return e.idvalue !== undefined;
    });
    if (this.comboSelectedAll) {
      this.getAuotonomia();
    }
  }

  getAuotonomia() {
    const obj: Rest = {
      type: 'POST',
      path: `${this.path}/autonomia/${this.data.idattivita}/${this.data.idprestazione}`,
      body: this.data.struttura
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.autonomiaList = res;
      }, (err) => {
    });
  }

  onSubmit()  {
    this.setData(this.idAttivita, this.data, this.idAttivita === '0' ? true : false);
  }

  delete(id: string, name: string) {
    this.dialog.openConfirm(this.translated.ELIMINA_ATTIVITA, this.translated.ELIMINA_ATTIVITA_SUB + ' ' + name + '?', this.translated.ELIMINA, this.translated.ANNULLA)
      .then(() => {
        const obj: Rest = {
          type: 'DELETE',
          path: `${this.path}/${id}`
        };
        this.main.rest(obj)
          .then((res: any) => {
            // this.getData();
          }, (err) => {
            this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, this.translated.CHIUDI);
          });
      }, (err) => {
      });
  }

  setData(id: string, body: any, insert = false) {
    const obj: Rest = {
      type: insert ? 'PUT' : 'POST',
      path: `${this.path}`,
      body
    };
    if (!insert) {
      obj.path = `${this.path}/${id}`;
    }
    this.main.rest(obj)
      .then(() => {
        this.router.navigate(['/attivita-list']);
      }, (err) => {
        this.dialog.openConfirm(this.translated.ATTENZIONE, err.error, 'Ok');
      });
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
