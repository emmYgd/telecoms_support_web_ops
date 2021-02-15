import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FundingHistoryDescriptionComponent } from './funding-history-description.component';

describe('FundingHistoryDescriptionComponent', () => {
  let component: FundingHistoryDescriptionComponent;
  let fixture: ComponentFixture<FundingHistoryDescriptionComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FundingHistoryDescriptionComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FundingHistoryDescriptionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
