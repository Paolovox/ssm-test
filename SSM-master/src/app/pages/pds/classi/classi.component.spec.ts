import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SettoriScientificiListComponent } from './settori-scientifici-list.component';

describe('ScuoleDiSpecializzazioneComponent', () => {
  let component: SettoriScientificiListComponent;
  let fixture: ComponentFixture<SettoriScientificiListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [SettoriScientificiListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SettoriScientificiListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
