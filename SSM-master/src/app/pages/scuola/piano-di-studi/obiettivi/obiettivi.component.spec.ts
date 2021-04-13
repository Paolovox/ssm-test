import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ObiettiviComponent } from './obiettivi.component';

describe('TipiAttivitaFormativeComponent', () => {
  let component: ObiettiviComponent;
  let fixture: ComponentFixture<ObiettiviComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ObiettiviComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ObiettiviComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
