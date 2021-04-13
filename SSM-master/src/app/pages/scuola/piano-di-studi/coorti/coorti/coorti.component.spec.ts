import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoortiComponent } from './coorti.component';

describe('CoortiComponent', () => {
  let component: CoortiComponent;
  let fixture: ComponentFixture<CoortiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CoortiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoortiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
