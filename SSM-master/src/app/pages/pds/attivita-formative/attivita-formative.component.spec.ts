import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaFormativeComponent } from './attivita-formative.component';

describe('ScuoleDiSpecializzazioneComponent', () => {
  let component: AttivitaFormativeComponent;
  let fixture: ComponentFixture<AttivitaFormativeComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaFormativeComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaFormativeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
