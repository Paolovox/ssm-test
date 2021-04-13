import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AssociazioneUnitaComponent } from './unita.component';

describe('AssociazioneUnitaComponent', () => {
  let component: AssociazioneUnitaComponent;
  let fixture: ComponentFixture<AssociazioneUnitaComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AssociazioneUnitaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AssociazioneUnitaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
