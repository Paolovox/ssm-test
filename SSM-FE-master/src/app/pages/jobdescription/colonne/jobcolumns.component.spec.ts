import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { JobColumnsComponent } from './jobcolumns.component';

describe('JobColumnsComponent', () => {
  let component: JobColumnsComponent;
  let fixture: ComponentFixture<JobColumnsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ JobColumnsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobColumnsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
