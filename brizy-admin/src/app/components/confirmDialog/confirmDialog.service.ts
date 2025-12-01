import { Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Observable } from 'rxjs';
import { ConfirmDialogComponent } from './confirmDialog.component';


@Injectable({
    providedIn: 'root'
})
export class ConfirmDialogService {

    constructor(
        private dialog: MatDialog
    ) {

    }

    openConfirmDialog(text: string, textAlign = 'center', canCancel = true, confirmText = 'OK'): Observable<boolean> {
        const dialogRef = this.dialog.open(ConfirmDialogComponent, {
            width: '430px',
            data: {
                text, textAlign, canCancel, confirmText
            }
        });

        return dialogRef.afterClosed();
    }
}
