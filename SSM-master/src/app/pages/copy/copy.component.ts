import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, DialogFields, OGModalEvents, DialogResponse,
  OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subject, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { MatPaginator } from '@angular/material/paginator';

@Component({
  selector: 'app-copy',
  templateUrl: './copy.component.html',
  styleUrls: ['./copy.component.scss']
})
export class CopyComponent implements OnInit, OnDestroy {

  path = 'copy';
  idAteneoDa: string;
  idAteneoA: string;
  idScuolaDa: string;
  idScuolaA: string;
  idTipo: string;

  atenei: Array<any> = [];
  scuoleDa: Array<any> = [];
  scuoleA: Array<any> = [];
  tipi: Array<any> = [
    { id: 1, text: 'Attività non professionalizzanti' },
    { id: 2, text: 'Prestazioni' },
    { id: 3, text: 'Combo attività' }
  ];

  search$: Subscription;
  router$: Subscription;

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private dialog: Dialog,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute
  ) {
  }

  ngOnInit() {
    this.getAtenei();
    this.pageTitleService.setTitle('Clonazione dati', '');
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      // this.getData(true, false);
    });
  }

  ngOnDestroy() {
    this.router$.unsubscribe();
  }

  getAtenei() {
    const obj: Rest = {
      type: 'GET',
      path: `atenei`,
      queryParams: {
        c: 1000
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.atenei = res.rows;
      }, (err) => {
    });
  }

  getScuole(idAteneo: string, tipo: number) {
    const obj: Rest = {
      type: 'GET',
      path: `scuole_di_specializzazione/${idAteneo}`,
      queryParams: {
        c: 1000
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        if (tipo === 1) {
          this.idScuolaDa = '';
          this.scuoleDa = res.rows;
        }
        if (tipo === 2) {
          this.idScuolaA = '';
          this.scuoleA = res.rows;
        }
      }, (err) => {
    });
  }

  async clone() {
    const ret = await this.dialog.openConfirm('Attenzione', 'Sei sicuro di voler copiare nella \
    scuola selezionata? L\'operazione è IRREVERSIBILE.', 'Si', 'Annulla').catch(err => false);
    console.log(ret);
    if (!ret) {
      return;
    }
    const obj: Rest = {
      type: 'POST',
      path: `clona`,
      body: {
        idscuola_da: this.idScuolaDa,
        idscuola_a: this.idScuolaA,
        idtipo: this.idTipo
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.dialog.openConfirm('Operazione completata', 'I dati sono stati clonati con successo.', 'Ok');
      }, (err) => {
        this.dialog.openConfirm('Attenzione', err.error, 'Ok');
      });
  }

}
