import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable()
export class PageTitleService {
    public title: BehaviorSubject<any> = new BehaviorSubject<any>({});

    setTitle(name: string, url: string) {
        this.title.next({name, url});
    }
}
