import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { MembershipLevelComponent } from './membership-level.component';

describe('MembershipLevelComponent', () => {
  let component: MembershipLevelComponent;
  let fixture: ComponentFixture<MembershipLevelComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MembershipLevelComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MembershipLevelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
