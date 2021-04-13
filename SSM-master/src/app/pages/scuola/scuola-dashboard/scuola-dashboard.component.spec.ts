import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ScuolaDashboardComponent } from './scuola-dashboard.component';

describe('ScuolaDashboardComponent', () => {
  let component: ScuolaDashboardComponent;
  let fixture: ComponentFixture<ScuolaDashboardComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ScuolaDashboardComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ScuolaDashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
