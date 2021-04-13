import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AssociazionePresidiComponent } from './presidi.component';

describe('AssociazionePresidiComponent', () => {
  let component: AssociazionePresidiComponent;
  let fixture: ComponentFixture<AssociazionePresidiComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AssociazionePresidiComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AssociazionePresidiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
