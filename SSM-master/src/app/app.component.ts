import { Component } from '@angular/core';
import { MainUtilsService } from '@ottimis/angular-utils';
import { debounceTime } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {

  title = 'Easy Assets';
  logged: boolean;
  ready = false;

  constructor(
    private main: MainUtilsService
  ) {
    this.main.logged.pipe(
      debounceTime(400)
    ).subscribe((res) => {
      this.ready = true;
      this.logged = res;
    });
  }
}
