import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AmbitiDisciplinariComponent } from './ambiti-disciplinari.component';

describe('AmbitiDisciplinariComponent', () => {
  let component: AmbitiDisciplinariComponent;
  let fixture: ComponentFixture<AmbitiDisciplinariComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AmbitiDisciplinariComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AmbitiDisciplinariComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
