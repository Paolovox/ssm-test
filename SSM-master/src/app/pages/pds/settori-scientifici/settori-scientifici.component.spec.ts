import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SettoriScientificiComponent } from './settori-scientifici.component';

describe('ScuoleDiSpecializzazioneComponent', () => {
  let component: SettoriScientificiComponent;
  let fixture: ComponentFixture<SettoriScientificiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SettoriScientificiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SettoriScientificiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
