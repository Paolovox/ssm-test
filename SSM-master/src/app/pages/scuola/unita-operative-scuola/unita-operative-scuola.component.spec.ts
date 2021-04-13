import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { UnitaOperativeScuolaComponent } from './unita-operative-scuola.component';

describe('UnitaOperativeComponent', () => {
  let component: UnitaOperativeScuolaComponent;
  let fixture: ComponentFixture<UnitaOperativeScuolaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [UnitaOperativeScuolaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(UnitaOperativeScuolaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
