import { Injectable } from '@angular/core';
import { BehaviorSubject, Subscriber, Observable } from 'rxjs';

@Injectable()
export class SearchService {

    public $search: Subscriber<string>;
    public searchText = '';

    listen(): Observable<string> {
        return new Observable((observer: Subscriber<string>) => {
            observer.next('');
            this.$search = observer;
        });
    }

    clear() {
        this.searchText = '';
        this.$search.next('');
    }
}
