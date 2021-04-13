import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaSchemaComponent } from './attivita-schema.component';

describe('AttivitaSchemaComponent', () => {
  let component: AttivitaSchemaComponent;
  let fixture: ComponentFixture<AttivitaSchemaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaSchemaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaSchemaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
