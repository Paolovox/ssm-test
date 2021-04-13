import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TurniComponent } from './turni.component';

describe('AttivitaComponent', () => {
  let component: TurniComponent;
  let fixture: ComponentFixture<TurniComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TurniComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TurniComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
