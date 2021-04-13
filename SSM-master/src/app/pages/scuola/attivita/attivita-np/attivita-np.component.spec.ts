import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttivitaNpComponent } from './attivita-np.component';

describe('AttivitaNpComponent', () => {
  let component: AttivitaNpComponent;
  let fixture: ComponentFixture<AttivitaNpComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttivitaNpComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttivitaNpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
