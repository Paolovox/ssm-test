import { Component, HostListener, isDevMode } from '@angular/core';
import { MainUtilsService } from '@ottimis/angular-utils';
import { CookieService } from 'ngx-cookie-service';
import { debounceTime } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {

  title = 'SSM';
  logged: boolean;
  ready = false;

  constructor(
    private main: MainUtilsService,
    private cookieService: CookieService
  ) {
    this.main.logged.pipe(
      debounceTime(400)
    ).subscribe((res) => {
      this.ready = true;
      this.logged = res;
    });
  }

  @HostListener('window:beforeunload', ['$event'])
  logout() {
    if (!isDevMode()) {
      this.main.logout();
      this.cookieService.deleteAll('/', '.unicatt.it');
    }
  }
}
