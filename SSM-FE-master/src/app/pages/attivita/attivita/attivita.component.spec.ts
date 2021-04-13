import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { AttivitaComponent } from './attivita.component';

describe('AttivitaComponent', () => {
  let component: AttivitaComponent;
  let fixture: ComponentFixture<AttivitaComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
