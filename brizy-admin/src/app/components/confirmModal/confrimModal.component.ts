import { Component, Input } from '@angular/core';
import { NgbActiveModal, NgbModal } from '@ng-bootstrap/ng-bootstrap';

@Component({
    selector: 'app-confirm-modal',
    templateUrl: './confirmModal.component.html',
    styleUrls: ['./confirmModal.component.scss'],
    standalone: true,
})
export class ConfirmModal {
    @Input() confirm;

    constructor(public activeModal: NgbActiveModal) {

    }
}
