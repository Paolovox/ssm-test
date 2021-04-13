import { Component, OnInit, ViewChild, AfterViewInit, OnDestroy, ViewEncapsulation } from '@angular/core';
import { PageTitleService } from 'src/app/core/page-title/page-title.service';
import { MainUtilsService, Dialog, Rest, OGModalComponent, OGListComponent, OGListSettings, OGListStyleType } from '@ottimis/angular-utils';
import { SearchService } from 'src/app/core/search/search.service';
import { debounceTime, filter } from 'rxjs/operators';
import { Observable, Subscription } from 'rxjs';
import { Router, RouterEvent, NavigationEnd, ActivatedRoute } from '@angular/router';
import { fadeInAnimation } from 'src/app/core/route-animation/route.animation';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-export',
  templateUrl: './export.component.html',
  styleUrls: ['./export.component.scss'],
  // tslint:disable-next-line:no-host-metadata-property
  host: {
    '[@fadeInAnimation]': 'true'
  },
  animations: [fadeInAnimation],
  encapsulation: ViewEncapsulation.None
})
export class ExportComponent implements OnInit, OnDestroy {

  path = 'specializzando_valutazioni';
  idScuola: string;
  idSpecializzando: string;
  nomeSpecializzando: string;

  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
  search$: Subscription;
  router$: Subscription;

  contatori: Array<any>;
  translated: any = {};

  constructor(
    private pageTitleService: PageTitleService,
    private main: MainUtilsService,
    private searchService: SearchService,
    private router: Router,
    private aRoute: ActivatedRoute,
    public translate: TranslateService
  ) {
    this.translate.get('EXPORT')
      .subscribe((res: any) => {
        this.translated = res;
      });
    this.aRoute.queryParamMap.subscribe((params) => {
      this.idSpecializzando = params.get('idSpecializzando');
      if (this.idSpecializzando && this.main.getUserData('idruolo') === '8')  {
        this.router.navigate(['/']);
      }
    });
  }

  ngOnInit() {
    this.idScuola = this.main.getUserData('idScuola');
    this.pageTitleService.setTitle(this.translated.MIEI_CONTATORI, '');
    this.search$ = this.searchService.listen()
      .pipe(
        debounceTime(200))
      .subscribe((search) => {
        this.getContatori();
      });
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      this.getContatori();
    });
  }

  ngOnDestroy() {
    this.searchService.clear();
    this.search$.unsubscribe();
    this.router$.unsubscribe();
  }

  getContatori() {
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}/export`,
      queryParams: {
        idSpecializzando: this.idSpecializzando ? this.idSpecializzando : ''
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.contatori = res.contatori;
        this.nomeSpecializzando = res.specializzando;
      }, (err) => {
    });
  }

  isTutor() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 7) {
      return true;
    }
  }

  isSpec() {
    const idRole = this.main.getUserData('idruolo');
    if (idRole && parseInt(idRole, 10) === 8) {
      return true;
    }
  }

}
