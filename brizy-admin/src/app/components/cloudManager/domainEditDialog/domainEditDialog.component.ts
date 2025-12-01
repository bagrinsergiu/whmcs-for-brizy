import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { CloudService } from 'src/app/services/cloud.service';

@Component({
    selector: 'app-cloud-domain-edit-dialog',
    templateUrl: 'domainEditDialog.component.html',
    styleUrls: ['./domainEditDialog.component.scss']
}) export class DomainEditDialog implements OnInit {

    domain = '';
    loading = false;

    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
        public dialogRef: MatDialogRef<DomainEditDialog>,
        @Inject(MAT_DIALOG_DATA) public data: { project: CloudProject; serviceId: number, processing: boolean, subdomain: boolean}) {
    }


    ngOnInit() {
        const subdomain = this.data.project.domains.find((domain) => domain.type === 'subdomain');
        const domain = this.data.project.domains.find((domain) => domain.type === 'third-party');
        if (this.data.subdomain) {
            this.domain = subdomain?.name || '';
        } else {
            this.domain = domain?.name || '';
        }
    }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    saveProjectDomain() {
        this.loading = true;
        this.data.project.processing = true;

        this.cloudService.changeProjectDomain( this.data.project.id, this.data.project.domains[0].id, this.domain, this.data.subdomain).subscribe((data)=> {
            this.loading = false;
            this.data.project.processing = false;
            this.dialogRef.close(true);
            this.toastr.success('The domain of the project has been changed');
        }, (error) => {
            this.data.project.processing = false;
            this.loading = false;
        });

    }
}
