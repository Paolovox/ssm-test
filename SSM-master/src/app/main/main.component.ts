import { Component, OnInit, ViewChild, ViewEncapsulation, AfterViewChecked, ChangeDetectorRef} from '@angular/core';
import { MenuItems, MenuTypes } from '../core/menu/menu-items/menu-items';
import { BreadcrumbService } from 'ng5-breadcrumb';
import { PageTitleService } from '../core/page-title/page-title.service';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { MainUtilsService } from '@ottimis/angular-utils';
import { SearchService } from '../core/search/search.service';
declare var require;

const screenfull = require('screenfull');

@Component({
 selector: 'app-main-layout',
 templateUrl: './main-material.html',
 styleUrls: ['./main-material.scss'],
 encapsulation: ViewEncapsulation.None,
})

export class MainComponent implements OnInit, AfterViewChecked {

 currentUrl: any;
 root: any = 'ltr';
 layout: any = 'ltr';
 currentLang: any = 'en';
 customizerIn = false;
 showSettings = false;
 chatpanelOpen = false;
 sidenavOpen = true;
 isMobile = false;
 isFullscreen = false;
 collapseSidebarStatus: boolean;
 headerName: string;
 headerUrl: string;
 dark: boolean;
 compactSidebar: boolean;
 isMobileStatus: boolean;
 sidenavMode = 'side';
 popupDeleteResponse: any;
 sidebarColor: any;
 url: string;
 windowSize: number;
 collapseSidebarB = false;
 // tslint:disable-next-line:variable-name
 private _routerEventsSubscription: Subscription;
 // tslint:disable-next-line:variable-name
 private _router: Subscription;
 @ViewChild('sidenav', {static : true}) sidenav;

 sideBarFilterClass: any = [
  {
   sideBarSelect  : 'sidebar-color-1',
   colorSelect    : 'sidebar-color-dark'
  },
  {
   sideBarSelect  : 'sidebar-color-2',
   colorSelect    : 'sidebar-color-primary',
  },
  {
   sideBarSelect  : 'sidebar-color-3',
   colorSelect    : 'sidebar-color-accent'
  },
  {
   sideBarSelect  : 'sidebar-color-4',
   colorSelect    : 'sidebar-color-warn'
  },
  {
   sideBarSelect  : 'sidebar-color-5',
   colorSelect    : 'sidebar-color-green'
  }
 ];

 headerFilterClass: any = [
  {
   headerSelect  : 'header-color-1',
   colorSelect   : 'header-color-dark'
  },
  {
   headerSelect  : 'header-color-2',
   colorSelect   : 'header-color-primary'
  },
  {
   headerSelect  : 'header-color-3',
   colorSelect   : 'header-color-accent'
  },
  {
   headerSelect  : 'header-color-4',
   colorSelect   : 'header-color-warning'
  },
  {
   headerSelect  : 'header-color-5',
   colorSelect   : 'header-color-green'
  }
 ];

 chatList: any [] = [
  {
   image : 'assets/img/user-1.jpg',
   name: 'John Smith',
   chat : 'Lorem ipsum simply dummy',
   mode : 'online'
  },
  {
   image : 'assets/img/user-2.jpg',
   name: 'Amanda Brown',
   chat : 'Lorem ipsum simply dummy',
   mode : 'online'
  },
  {
   image : 'assets/img/user-3.jpg',
   name: 'Justin Randolf',
   chat : 'Lorem ipsum simply dummy',
   mode : 'offline'
  },
  {
   image : 'assets/img/user-4.jpg',
   name: 'Randy SunSung',
   chat : 'Lorem ipsum simply dummy',
   mode : 'online'
  },
  {
   image : 'assets/img/user-5.jpg',
   name: 'Lisa Myth',
   chat : 'Lorem ipsum simply dummy',
   mode : 'online'
  },
 ];

 user: any;

 constructor(
  public menuItems: MenuItems,
  private breadcrumbService: BreadcrumbService,
  private pageTitleService: PageTitleService,
  public searchService: SearchService,
  private main: MainUtilsService,
  private router: Router,
  private changeDect: ChangeDetectorRef
) {
  breadcrumbService.addFriendlyNameForRoute('/atenei', 'Atenei');
  breadcrumbService.addFriendlyNameForRouteRegex('^\/atenei\/.*', 'Scuole di specializzazione');
  breadcrumbService.addFriendlyNameForRouteRegex('^\/atenei\/.*\/.*', 'Attività');
  breadcrumbService.addFriendlyNameForRoute('/attivita', 'Attività');
  breadcrumbService.addFriendlyNameForRoute('/attivita/ambiti-disciplinari', 'Ambiti disciplinari');
  breadcrumbService.addFriendlyNameForRoute('/attivita/settori-scientifici', 'Settori scientifici');
  breadcrumbService.addFriendlyNameForRoute('/attivita/tipi-attivita-formative', 'Tipi di attivita formative');
 }

 ngOnInit() {
    const userTemp = localStorage.getItem('user');
    this.user = userTemp ? JSON.parse(userTemp) : {};
    const idScuola = this.main.getUserData('idScuola');
    if (idScuola !== false)  {
      this.router.navigate([`${idScuola}/dashboard`]);
    }
 }

  ngAfterViewChecked() {
    this.pageTitleService.title
      .subscribe((val: any) => {
        this.headerName = val.name;
        this.headerUrl = val.url;
        this.changeDect.detectChanges();
      });
 }

 //  As router outlet will emit an activate event any time a new component is being instantiated.
 onActivate(e, scrollContainer) {
  scrollContainer.scrollTop = 0;
 }


  // toggleFullscreen method is used to show a template in fullscreen.

 toggleFullscreen() {
  if (screenfull.enabled) {
   screenfull.toggle();
   this.isFullscreen = !this.isFullscreen;
  }
 }


  // customizerFunction is used to open and close the customizer.

 customizerFunction() {
  this.customizerIn = !this.customizerIn;
 }


  // addClassOnBody method is used to add a add or remove class on body.

 addClassOnBody(event) {
  const body = document.body;
  if (event.checked) {
   body.classList.add('dark-theme-active');
  } else {
   body.classList.remove('dark-theme-active');
  }
 }


  // changeRTL method is used to change the layout of template.

 changeRTL(isChecked) {
  if (isChecked) {
   this.layout = 'rtl';
  } else {
   this.layout = 'ltr';
  }
 }


  // toggleSidebar method is used a toggle a side nav bar.

 toggleSidebar() {
  this.sidenavOpen = !this.sidenavOpen;
 }


  // logOut method is used to log out the  template.

 logOut() {
  this.main.logout();
  this.router.navigate(['login']);
  this.backToStandard();
 }

  backToStandard() {
    this.menuItems.switchMenu(MenuTypes.STANDARD);
    this.main.deleteUserData('idScuola');
    this.main.deleteUserData('nomeScuola');
    this.main.deleteUserData('idAteneo');
  }

  // sidebarFilter function filter the color for sidebar section.
 sidebarFilter(selectedFilter) {
  // tslint:disable-next-line:prefer-for-of
  for (let i = 0; i < this.sideBarFilterClass.length; i++) {
   document.getElementById('main-app').classList.remove(this.sideBarFilterClass[i].colorSelect);
   if (this.sideBarFilterClass[i].colorSelect === selectedFilter.colorSelect) {
    document.getElementById('main-app').classList.add(this.sideBarFilterClass[i].colorSelect);
   }
  }
  document.querySelector('.radius-circle').classList.remove('radius-circle');
  document.getElementById(selectedFilter.sideBarSelect).classList.add('radius-circle');
 }

  // headerFilter function filter the color for header section.
 headerFilter(selectedFilter) {
  // tslint:disable-next-line:prefer-for-of
  for (let i = 0; i < this.headerFilterClass.length; i++) {
   document.getElementById('main-app').classList.remove(this.headerFilterClass[i].colorSelect);
   if (this.headerFilterClass[i].colorSelect === selectedFilter.colorSelect) {
    document.getElementById('main-app').classList.add(this.headerFilterClass[i].colorSelect);
   }
  }
  document.querySelector('.radius-active').classList.remove('radius-active');
  document.getElementById(selectedFilter.headerSelect).classList.add('radius-active');
 }

//  chatMenu method is used to toggle a chat menu list;
 chatMenu() {
  document.getElementById('gene-chat').classList.toggle('show-chat-list');
 }


  // onChatOpen method is used to open a chat window.
 onChatOpen() {
  document.getElementById('chat-open').classList.toggle('show-chat-window');
 }


  // onChatWindowClose method is used to close the chat window.
 chatWindowClose() {
  document.getElementById('chat-open').classList.remove('show-chat-window');
 }

 collapseSidebar(event) {
  if (event.checked) {
    this.collapseSidebarB = true;
  } else {
    this.collapseSidebarB = false;
  }
 }
}
