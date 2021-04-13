import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { JobDatiComponent } from './dati.component';

describe('JobColumnsComponent', () => {
  let component: JobDatiComponent;
  let fixture: ComponentFixture<JobDatiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ JobDatiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobDatiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
