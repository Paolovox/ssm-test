import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaComboComponent } from './attivita-combo.component';

describe('AttivitaComponent', () => {
  let component: AttivitaComboComponent;
  let fixture: ComponentFixture<AttivitaComboComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaComboComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaComboComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
