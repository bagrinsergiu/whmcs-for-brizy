
import { Injectable } from '@angular/core';
import {
    HttpRequest,
    HttpHandler,
    HttpEvent,
    HttpInterceptor,
    HttpErrorResponse
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';

@Injectable()
export class ErrorInterceptor implements HttpInterceptor {

    constructor(
        private toastr: ToastrService
    ) {

    }

    intercept(
        request: HttpRequest<any>,
        next: HttpHandler
    ): Observable<HttpEvent<any>> {
        // show loading spinner

        return next.handle(request).pipe(
            catchError((response: HttpErrorResponse) => {
                if (response.url.includes('i18n')) {
                    return;
                }

                let errors = response.error.errors;
                let errorString = '';

                if (typeof response?.error?.data?.error?.message !== 'undefined') {
                    errorString = errorString + response.error.data.error.message;
                }

                if (errorString) {
                    this.toastr.error(errorString, null, { enableHtml: true });
                } else {
                    this.toastr.error('A critical error has occurred, please contact your application provider....', null, { enableHtml: true });
                }


                return next.handle(request);
            }),
            finalize(() => {
                // hide loading spinner

            })
        ) as Observable<HttpEvent<any>>;
    }
}
