import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { OGModalListComponent } from './ogmodal-list.component';

describe('OGModalComponent', () => {
  let component: OGModalListComponent;
  let fixture: ComponentFixture<OGModalListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ OGModalListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(OGModalListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
