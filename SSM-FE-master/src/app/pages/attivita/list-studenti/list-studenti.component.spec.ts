import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { ListStudentiComponent } from './list-studenti.component';

describe('ListStudentiComponent', () => {
  let component: ListStudentiComponent;
  let fixture: ComponentFixture<ListStudentiComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ListStudentiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListStudentiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
