import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { AiProjectsLocalStorageService } from 'src/app/services/aiProjectsLocalStorage.service';
import { CloudService } from 'src/app/services/cloud.service';

@Component({
    selector: 'app-cloud-ai-project-edit-dialog',
    templateUrl: 'aiProjectEditDialog.component.html',
    styleUrls: ['./aiProjectEditDialog.component.scss']
}) export class AiProjectEditDialog implements OnInit {

    projectId = '686e315c392334.62526788';
    projectPreviewUrl = 'https://ai.mysitebuilder.online/project/view/686e315c392334.62526788';
    saving = false;

    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
        public dialogRef: MatDialogRef<AiProjectEditDialog>,
        private aiProjectsLocalStorage: AiProjectsLocalStorageService,
        @Inject(MAT_DIALOG_DATA) public data: { title:string, id: string; url: string}) {
    }


    ngOnInit() {

    }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    openAiEditSite() {
        window.open(this.data.url, '_blank').focus();
    }

    saveAiProject() {
        this.saving = true;

        this.cloudService.finishWebsite(this.data.id).subscribe((respons) =>  {
            this.saving = false;
            this.aiProjectsLocalStorage.remove(this.data.id);
            this.ok();
        }, errorResponse => {
            this.saving = false;
        });

    }
}
