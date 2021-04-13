import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SospensiveComponent } from './sospensive.component';

describe('SospensiveComponent', () => {
  let component: SospensiveComponent;
  let fixture: ComponentFixture<SospensiveComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SospensiveComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SospensiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
