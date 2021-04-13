import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InsegnamentiComponent } from './insegnamenti.component';

describe('InsegnamentiComponent', () => {
  let component: InsegnamentiComponent;
  let fixture: ComponentFixture<InsegnamentiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ InsegnamentiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(InsegnamentiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
