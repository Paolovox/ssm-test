import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaNpDatiAggiuntiviComponent } from './attivita-np-dati-aggiuntivi.component';

describe('AttivitaNpDatiAggiuntiviComponent', () => {
  let component: AttivitaNpDatiAggiuntiviComponent;
  let fixture: ComponentFixture<AttivitaNpDatiAggiuntiviComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaNpDatiAggiuntiviComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaNpDatiAggiuntiviComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
