import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { OGListComponent } from './oglist.component';

describe('OGListComponent', () => {
  let component: OGListComponent;
  let fixture: ComponentFixture<OGListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ OGListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(OGListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
