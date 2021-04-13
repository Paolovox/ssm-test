import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ScuoleDiSpecializzazioneComponent } from './scuole-di-specializzazione.component';

describe('ScuoleDiSpecializzazioneComponent', () => {
  let component: ScuoleDiSpecializzazioneComponent;
  let fixture: ComponentFixture<ScuoleDiSpecializzazioneComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ScuoleDiSpecializzazioneComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ScuoleDiSpecializzazioneComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
