import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { CloudService } from 'src/app/services/cloud.service';

@Component({
    selector: 'app-cloud-rename-dialog',
    templateUrl: 'renameDialog.component.html',
    styleUrls: ['./renameDialog.component.scss']
}) export class RenameDialogComponent implements OnInit {

    projectName = '';
    loading = false;

    constructor(
        public dialogRef: MatDialogRef<RenameDialogComponent>,
        private cloudService: CloudService,
        private toastr: ToastrService,
        @Inject(MAT_DIALOG_DATA) public data: { project: CloudProject; serviceId: number, processing: boolean}) {

    }


    ngOnInit() {
        this.projectName = this.data.project.name;
    }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    saveProjectName() {
        this.loading = true;
        this.data.project.processing = true;

        this.cloudService.renameProject(this.data.project.id, this.projectName).subscribe((data)=> {
            this.data.project.name = this.projectName + '';
            this.data.project.processing = false;
            this.loading = false;
            this.ok();
            this.toastr.success('The name of the project has been changed');
        }, (error) => {
            this.data.project.processing = false;
            this.loading = false;
        });

    }
}
