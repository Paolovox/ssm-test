import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaFiltriComponent } from './attivita-filtri.component';

describe('AttivitaFiltriComponent', () => {
  let component: AttivitaFiltriComponent;
  let fixture: ComponentFixture<AttivitaFiltriComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaFiltriComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaFiltriComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
