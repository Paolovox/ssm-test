import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { ListSurveyComponent } from './list.component';

describe('ListSurveyComponent', () => {
  let component: ListSurveyComponent;
  let fixture: ComponentFixture<ListSurveyComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ListSurveyComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListSurveyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
