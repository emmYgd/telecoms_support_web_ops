import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CreateTransactionPinComponent } from './create-transaction-pin.component';

describe('CreateTransactionPinComponent', () => {
  let component: CreateTransactionPinComponent;
  let fixture: ComponentFixture<CreateTransactionPinComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CreateTransactionPinComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CreateTransactionPinComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
