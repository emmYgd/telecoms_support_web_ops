import { Injectable, Inject } from "@angular/core";
import {
  MatSnackBar,
  MatSnackBarConfig,
  MatDialog,
  MatDialogConfig,
  TOOLTIP_PANEL_CLASS,
  MatMonthView
} from "@angular/material";
import {
  BreakpointObserver,
  Breakpoints,
  BreakpointState,
  MediaMatcher
} from "@angular/cdk/layout";
import { Observable } from "rxjs";

@Injectable()
export class UiService {
  isExtraSmall: Observable<BreakpointState> = this.breakpointObserver.observe(
    Breakpoints.XSmall
  );

  mobileQuery: MediaQueryList;
  private dialogRef: any;
  private _mobileQueryListener: () => void;

  constructor(
    private snackBar: MatSnackBar,
    public media: MediaMatcher,
    private dialog: MatDialog,
    private breakpointObserver: BreakpointObserver
  ) {
    this.mobileQuery = media.matchMedia("(max-width: 600px)");
    this._mobileQueryListener = () => {
      // changeDetectorRef.detectChanges();
    };

    this.mobileQuery.addListener(this._mobileQueryListener);
  }

  showToast(message: string, action = "Close", config?: MatSnackBarConfig) {
    this.snackBar.open(
      message,
      action,
      config || {
        duration: 7000,
        horizontalPosition: "right",
        verticalPosition: "top"
      }
    );
  }

  opendialog(component, data: any) {
    const dialogRef = this.dialog.open(component, {
      width: "500px",
      maxWidth: "100%",
      data: { data }
    });

    const smallDialogSubscription = this.isExtraSmall.subscribe(size => {
      if (size.matches) {
        dialogRef.updateSize("100%", "100%");
      } else {
        dialogRef.updateSize("500px", "");
      }
    });

    dialogRef.afterClosed().subscribe(() => {
      smallDialogSubscription.unsubscribe();
    });

    return dialogRef.afterClosed();
  }

  opendialogv2(component, data: any, callable) {
    const dialogRef = this.dialog.open(component, {
      width: "500px",
      maxWidth: "100%",
      data: { data }
    });

    const smallDialogSubscription = this.isExtraSmall.subscribe(size => {
      if (size.matches) {
        dialogRef.updateSize("100%", "100%");
      } else {
        dialogRef.updateSize("500px", "");
      }
    });

    dialogRef.afterClosed().subscribe(r => {
      smallDialogSubscription.unsubscribe();
      callable(r);
    });
  }

  openDialogv3(
    component,
    detectorRef,
    dialogData,
    callable,
    normalSize: string = "450px",
    containerClass: string = ""
  ) {
    if (
      normalSize == null ||
      normalSize == undefined ||
      normalSize == "" ||
      normalSize.length == 0
    ) {
      normalSize == "600px";
    }

    if (detectorRef != null) {
      this._mobileQueryListener = () => {
        detectorRef.detectChanges();
        if (this.dialogRef != null) {
          if (this.mobileQuery.matches) {
            this.dialogRef.updateSize("100vw", "100vh");
          } else {
            this.dialogRef.updateSize(normalSize, "auto");
          }
        }
      };

      this.mobileQuery.removeListener(this._mobileQueryListener);

      this.mobileQuery.addListener(this._mobileQueryListener);
    }

    if (this.mobileQuery.matches) {
      this.dialogRef = this.dialog.open(component, {
        width: "100vw", //we can use breakpoint observer or media query to customize width of dialog here.
        height: "100vh",
        maxWidth: "none",
        panelClass: containerClass,
        data: dialogData
      });
      this.dialogRef.afterClosed().subscribe(result => {
        callable(result);
      });
    } else {
      this.dialogRef = this.dialog.open(component, {
        width: normalSize, //we can use breakpoint observer or media query to customize width of dialog here.
        maxWidth: "100vw",
        panelClass: containerClass,
        data: dialogData
      });

      this.dialogRef.afterClosed().subscribe(result => {
        callable(result);
      });
    }
  }

}
