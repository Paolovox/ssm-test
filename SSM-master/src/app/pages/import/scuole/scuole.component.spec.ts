import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AssociazioneScuoleComponent } from './scuole.component';

describe('AssociazioneScuoleComponent', () => {
  let component: AssociazioneScuoleComponent;
  let fixture: ComponentFixture<AssociazioneScuoleComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AssociazioneScuoleComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AssociazioneScuoleComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
