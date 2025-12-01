import { DomainEditDialog } from './../domainEditDialog/domainEditDialog.component';
import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { CloudService } from 'src/app/services/cloud.service';
import { ConfirmDialogService } from '../../confirmDialog/confirmdialog.service';
import { DeployProjectDialog } from '../deployDialog/deployDialog.component';

@Component({
    selector: 'app-cloud-publish-dialog',
    templateUrl: 'publishDialog.component.html',
    styleUrls: ['./publishDialog.component.scss']
}) export class PublishDialogComponent {

    subdomain = null;
    domain = null;
    loading = false;

    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
        public dialogRef: MatDialogRef<PublishDialogComponent>,
        private confirmService: ConfirmDialogService,
        @Inject(MAT_DIALOG_DATA) public data: {project: CloudProject, serviceId: number, deploy: number}) {
            this.subdomain = data.project.domains.find((domain) => domain.type === 'subdomain');
            this.domain = data.project.domains.find((domain) => domain.type === 'third-party');
        }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }


    domainEdit(subdomain = false) {
        const dialogRef = this.dialog.open(DomainEditDialog, {
            data: {
                project: this.data.project,
                serviceId: this.data.serviceId,
                subdomain: subdomain
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }

            this.ok();
        });
    }

    domainDelete() {

        this.confirmService.openConfirmDialog(
            `Are you sure you want to remove this domain?<br/><strong>${this.domain.name}</strong>`,
        ).subscribe(
            (proceed) => {
                if (!proceed) {
                    return;
                }
                this.loading = true;
                this.cloudService.deleteProjectDomain(this.data.project.id, this.domain.id).subscribe((data)=> {
                    this.loading = false;
                    this.data.project.processing = false;
                    this.dialogRef.close(true);
                    this.toastr.success('The domain for the project has been removed');
                }, (error) => {
                    this.data.project.processing = false;
                    this.loading = false;
                });
            }
        );
    }

    deploy() {
        const dialogRef = this.dialog.open(DeployProjectDialog, {
            data: {
                project: this.data.project,
                serviceId: this.data.serviceId,
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }

            this.ok();
        });
    }

    download() {
        let project = this.data.project;

        this.confirmService.openConfirmDialog(
            `<strong>${project.name}</strong><br/>You begin downloading the project<br/>Do you want to continue?`,
        ).subscribe(
            (proceed) => {
                if (!proceed) {
                    return;
                }
                this.loading = true;
                this.cloudService.getDownloadLink(project.id).subscribe( (response) => {
                    window.open(response.data.download_url, '_blank').focus();
                    this.loading = false;
                }, (error) => {
                    this.loading = false;
                });

            }
        );
    }
}
