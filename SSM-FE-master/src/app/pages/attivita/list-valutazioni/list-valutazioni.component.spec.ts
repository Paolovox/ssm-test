import { ComponentFixture, TestBed, waitForAsync } from '@angular/core/testing';

import { ListValutazioniComponent } from './list-valutazioni.component';

describe('ListValutazioniComponent', () => {
  let component: ListValutazioniComponent;
  let fixture: ComponentFixture<ListValutazioniComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ ListValutazioniComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListValutazioniComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
