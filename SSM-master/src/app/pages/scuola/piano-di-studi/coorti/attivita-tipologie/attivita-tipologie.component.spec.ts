import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaTipologieComponent } from './attivita-tipologie.component';

describe('AttivitaTipologieComponent', () => {
  let component: AttivitaTipologieComponent;
  let fixture: ComponentFixture<AttivitaTipologieComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaTipologieComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaTipologieComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
