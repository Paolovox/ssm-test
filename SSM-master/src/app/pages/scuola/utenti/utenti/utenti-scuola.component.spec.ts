import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { UtentiScuolaComponent } from './utenti-scuola.component';

describe('UtentiComponent', () => {
  let component: UtentiScuolaComponent;
  let fixture: ComponentFixture<UtentiScuolaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ UtentiScuolaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent( UtentiScuolaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
