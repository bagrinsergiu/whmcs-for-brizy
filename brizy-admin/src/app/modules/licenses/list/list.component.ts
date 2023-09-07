import { Component, OnInit } from '@angular/core';
import { ModalDismissReasons, NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { ConfirmModal } from 'src/app/components/confirmModal/confrimModal.component';
import { License } from 'src/app/interfaces/license.interface';
import { LicenseService } from 'src/app/services/licenses.service';

@Component({
    selector: 'app-licenses-list',
    templateUrl: './list.component.html',
    styleUrls: ['./list.component.scss']
})
export class LicensesListComponent implements OnInit {

    licenses: License[];
    loading = false;
    newLicense = '';
    newMultipleLicenses = '';
    showMultipleLicensesModal = false;
    defaultConfirmModalSettings = { backdrop: false, keyboard: false, centered: true, modalDialogClass: 'panel panel-primary', windowClass: 'modal whmcs-modal fade in show'};


    closeResult = '';
    constructor(
        public licenseService: LicenseService,
        private modalService: NgbModal
    ) { }

    ngOnInit() {
        this.getLicenses();
    }

    getLicenses() {
        this.loading = true;
        this.licenseService.getAll().subscribe(
            {
                next: (response) => {
                    this.licenses = response.data;
                    this.loading = false;
                },
                complete: () => {
                    this.loading = false;
                },
                error: () => {
                    this.loading = false;
                }
            }
        );
    }

    addLicense(licenseString) {

        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = 'Are you sure you want to generate a new license?';

        confrimModal.result.then((result) => {
            if (result) {
                this.loading = true;
                this.licenseService.add(licenseString).subscribe(
                    {
                        next: () => {
                            this.loading = false;
                            this.getLicenses();
                            this.newLicense = '';
                        },
                        error: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                    }
                );
            }

        }, (dismissReason) => {

        });

    }


    deleteLicense(license) {

        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = 'Are you sure you want to delete license?';

        confrimModal.result.then((result) => {
            if (result) {
                this.loading = true;
                this.licenseService.delete(license.id).subscribe(
                    {
                        next: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                        error: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                    }
                );
            }

        }, (dismissReason) => {

        });

    }

    revokeLicense(license) {

        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = 'Are you sure you want to revoke license?';

        confrimModal.result.then((result) => {
            if (result) {
                this.loading = true;
                this.licenseService.revoke(license.id).subscribe(
                    {
                        next: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                        error: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                    }
                );
            }

        }, (dismissReason) => {

        });


    }

    disableLicense(license) {
        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = 'Are you sure you want to disable license?';

        confrimModal.result.then((result) => {
            if (result) {
                this.loading = true;
                this.licenseService.disable(license.id).subscribe(
                    {
                        next: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                        error: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                    }
                );
            }

        }, (dismissReason) => {

        });
    }

    activateLicense(license) {
        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = 'Are you sure you want to activate license?';

        confrimModal.result.then((result) => {
            if (result) {
                this.loading = true;
                this.licenseService.activate(license.id).subscribe(
                    {
                        next: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                        error: () => {
                            this.loading = false;
                            this.getLicenses();
                        },
                    }
                );
            }

        }, (dismissReason) => {

        });
    }

    refreshList() {
        this.getLicenses();
    }
}
