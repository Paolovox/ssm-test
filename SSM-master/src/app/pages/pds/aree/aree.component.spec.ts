import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AreeComponent } from './aree.component';

describe('AreeComponent', () => {
  let component: AreeComponent;
  let fixture: ComponentFixture<AreeComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AreeComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AreeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
