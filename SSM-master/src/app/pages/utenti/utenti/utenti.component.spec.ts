import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { UtentiComponent } from './utenti.component';

describe('UtentiComponent', () => {
  let component: UtentiComponent;
  let fixture: ComponentFixture<UtentiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ UtentiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(UtentiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
