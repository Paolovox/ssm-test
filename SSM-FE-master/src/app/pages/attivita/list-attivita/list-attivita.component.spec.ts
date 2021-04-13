import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { ListAttivitaComponent } from './list-attivita.component';

describe('ListAttivitaComponent', () => {
  let component: ListAttivitaComponent;
  let fixture: ComponentFixture<ListAttivitaComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ListAttivitaComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListAttivitaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
