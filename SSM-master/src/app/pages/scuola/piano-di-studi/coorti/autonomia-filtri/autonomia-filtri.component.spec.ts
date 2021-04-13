import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AutonomiaFiltriComponent } from './autonomia-filtri.component';

describe('AutonomiaFiltriComponent', () => {
  let component: AutonomiaFiltriComponent;
  let fixture: ComponentFixture<AutonomiaFiltriComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AutonomiaFiltriComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AutonomiaFiltriComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
