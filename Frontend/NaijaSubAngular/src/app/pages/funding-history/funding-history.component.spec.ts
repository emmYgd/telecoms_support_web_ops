import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FundingHistoryComponent } from './funding-history.component';

describe('FundingHistoryComponent', () => {
  let component: FundingHistoryComponent;
  let fixture: ComponentFixture<FundingHistoryComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FundingHistoryComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FundingHistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
