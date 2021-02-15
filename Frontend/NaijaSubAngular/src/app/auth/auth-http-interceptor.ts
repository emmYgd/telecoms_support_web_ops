import {HttpEvent, HttpHandler, HttpInterceptor, HttpRequest,} from '@angular/common/http';
import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {Observable, throwError as observableThrowError} from 'rxjs';
import {catchError} from 'rxjs/operators';
import {AuthService} from './auth.service';

@Injectable()
export class AuthHttpInterceptor implements HttpInterceptor {
    constructor(private authService: AuthService, private router: Router) {
    }

    intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
        if (req.headers.get('skip')) {
            return next.handle(req);
        }
        const jwt = this.authService.getToken();
        const authRequest = req.clone({setHeaders: {authorization: `Bearer ${jwt}`}});
        return next.handle(authRequest).pipe(
            catchError((err, caught) => {
                if (err.status === 401) {
                    this.router.navigate(['/'], {
                        queryParams: {redirectUrl: this.router.routerState.snapshot.url},
                    });
                }
                return observableThrowError(err);
            })
        );
    }
}
