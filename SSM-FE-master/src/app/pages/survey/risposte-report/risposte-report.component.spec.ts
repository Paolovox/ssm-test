import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { RisposteReportComponent } from './risposte-report.component';

describe('RisposteReportComponent', () => {
  let component: RisposteReportComponent;
  let fixture: ComponentFixture<RisposteReportComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ RisposteReportComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(RisposteReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
