import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { UtenteScuolaComponent } from './utente-scuola.component';

describe('UtenteComponent', () => {
  let component: UtenteScuolaComponent;
  let fixture: ComponentFixture<UtenteScuolaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ UtenteScuolaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(UtenteScuolaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
