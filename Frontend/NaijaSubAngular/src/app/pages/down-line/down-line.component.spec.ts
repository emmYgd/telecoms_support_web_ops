import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DownLineComponent } from './down-line.component';

describe('DownLineComponent', () => {
  let component: DownLineComponent;
  let fixture: ComponentFixture<DownLineComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ DownLineComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DownLineComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
