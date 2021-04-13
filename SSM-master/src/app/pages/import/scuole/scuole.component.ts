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
  selector: 'app-scuole-associazione',
  templateUrl: './scuole.component.html',
  styleUrls: ['./scuole.component.scss']
})
export class AssociazioneScuoleComponent implements OnInit, OnDestroy {

  path = 'import/associazione/scuole';
  totalElement: number;
  pageElement = 0;

  scuoleAll: Array<any> = [];
  scuole: Array<any> = [];
  atenei: Array<any> = [];
  idAteneo = '';

  @ViewChild('paginator') paginator: MatPaginator;
  @ViewChild('scuoleAssociazioneTable') scuoleAssociazioneTable: OGListComponent;
  @ViewChild('OGModal') ogModal: OGModalComponent;
  data: any;
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

    this.getScuole();

    this.pageTitleService.setTitle('Associazione scuole', '');
    this.router$ = this.router.events.pipe(
      filter((event: RouterEvent) => event instanceof NavigationEnd)
    ).subscribe(() => {
      // this.getData(true, false);
    });
  }

  ngOnDestroy() {
    this.searchService.clear();
    this.router$.unsubscribe();
  }

  getScuole(e?) {
    if (e) {
      this.pageElement = e.pageIndex;
    }
    const obj: Rest = {
      type: 'GET',
      path: `${this.path}`,
      queryParams: {
        page: this.pageElement,
        idateneo: this.idAteneo
      }
    };
    this.main.rest(obj)
      .then((res: any) => {
        this.atenei = res.atenei;
        this.scuoleAll = res.scuole_all;
        this.scuole = res.scuole;
        this.totalElement = res.total;
      }, (err) => {
    });
  }

  saveScuole() {
    const obj: Rest = {
      type: 'PUT',
      path: `${this.path}`,
      queryParams: {
        idateneo: this.idAteneo
      },
      body: this.scuoleAll
    };
    this.main.rest(obj)
      .then((res: any) => {
        alert('ok');
      }, (err) => {
    });
  }

}
