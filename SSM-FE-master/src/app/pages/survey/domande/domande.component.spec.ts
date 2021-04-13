import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DomandeSurveyComponent } from './domande.component';

describe('DomandeSurveyComponent', () => {
  let component: DomandeSurveyComponent;
  let fixture: ComponentFixture<DomandeSurveyComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ DomandeSurveyComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DomandeSurveyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
