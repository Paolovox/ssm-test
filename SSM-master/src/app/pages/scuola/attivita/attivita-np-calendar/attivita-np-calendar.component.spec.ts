import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaNpCalendarComponent } from './attivita-np-calendar.component';

describe('AttivitaNpCalendarComponent', () => {
  let component: AttivitaNpCalendarComponent;
  let fixture: ComponentFixture<AttivitaNpCalendarComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaNpCalendarComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaNpCalendarComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
