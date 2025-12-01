import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { CloudService } from 'src/app/services/cloud.service';
import { ConfirmDialogService } from '../../confirmDialog/confirmdialog.service';

@Component({
    selector: 'app-cloud-project-deploy-dialog',
    templateUrl: 'deployDialog.component.html',
    styleUrls: ['./deployDialog.component.scss']
}) export class DeployProjectDialog implements OnInit {

    domain = '';
    loading = false;
    deploymentText = '';

    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
        public dialogRef: MatDialogRef<DeployProjectDialog>,
        private confirmService: ConfirmDialogService,
        @Inject(MAT_DIALOG_DATA) public data: { project: CloudProject; serviceId: number, processing: boolean}) {
    }


    ngOnInit() {

    }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    startDeployment() {
        this.confirmService.openConfirmDialog(
            `<strong>${this.data.project.name}</strong><br/>Your project will be uploaded to the account immediately. If there are any existing pages on this account, they might be overwritten<br/>Do you want to continue?`,
        ).subscribe(
            (proceed) => {
                if (!proceed) {
                    return;
                }
                this.loading = true;
                this.deploymentText = 'Initialization...';

                setTimeout(() => {
                    this.deploymentText = 'Downloading...';
                }, 2000);

                setTimeout(() => {
                    this.deploymentText = 'Deploying...';
                }, 4000);

                setTimeout(() => {
                    this.deploymentText = 'Finalizing...';
                }, 7000);

                this.cloudService.deployProject(this.data.project.id).subscribe( (response) => {
                    this.loading = false;
                    this.toastr.success('The project you selected has been successfully uploaded to your account - You can now visit your website');
                    this.ok();
                }, (error) => {
                    this.toastr.error('Failed to upload the selected project.');
                    this.loading = false;
                });

            }
        );
    }

}
