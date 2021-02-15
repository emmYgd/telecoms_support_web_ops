import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ServicesDetailsComponent } from './services-details.component';

describe('ServicesDetailsComponent', () => {
  let component: ServicesDetailsComponent;
  let fixture: ComponentFixture<ServicesDetailsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ServicesDetailsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ServicesDetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
