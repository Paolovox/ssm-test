import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { ListStudentiSurveyComponent } from './studenti.component';

describe('ListStudentiSurveyComponent', () => {
  let component: ListStudentiSurveyComponent;
  let fixture: ComponentFixture<ListStudentiSurveyComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ListStudentiSurveyComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListStudentiSurveyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
