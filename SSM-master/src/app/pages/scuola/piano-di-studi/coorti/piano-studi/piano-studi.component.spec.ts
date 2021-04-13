import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PianoStudiComponent } from './piano-studi.component';

describe('SettoriScientificiComponent', () => {
  let component: PianoStudiComponent;
  let fixture: ComponentFixture<PianoStudiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PianoStudiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PianoStudiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
