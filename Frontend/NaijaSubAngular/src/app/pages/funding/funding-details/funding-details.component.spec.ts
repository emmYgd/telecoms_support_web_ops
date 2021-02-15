import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FundingDetailsComponent } from './funding-details.component';

describe('FundingDetailsComponent', () => {
  let component: FundingDetailsComponent;
  let fixture: ComponentFixture<FundingDetailsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FundingDetailsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FundingDetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
