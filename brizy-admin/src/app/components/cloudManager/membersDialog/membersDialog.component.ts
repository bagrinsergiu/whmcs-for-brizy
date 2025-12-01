import { Component, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { TeamMember } from 'src/app/interfaces/member.interface';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { CloudService } from 'src/app/services/cloud.service';

@Component({
    selector: 'app-cloud-members-dialog',
    templateUrl: 'membersDialog.component.html',
    styleUrls: ['./membersDialog.component.scss']
}) export class MembersDialogComponent implements OnInit {


    public roles = [
        {
            name: 'Viewer',
            value: 'viewer'
        },
        {
            name: 'Editor',
            value: 'editor'
        },
        {
            name: 'Designer',
            value: 'designer'
        },
        {
            name: 'Manager',
            value: 'manager'
        },

    ];

    public loadingTeamMembers = true;
    public teamMembers: TeamMember[] = [];
    private serviceId: number;

    emailFormControl = new FormControl('',  [Validators.required, Validators.email]);
    roleFormControl = new FormControl('viewer', [Validators.required] );

    constructor(
        public dialogRef: MatDialogRef<MembersDialogComponent>,
        private toastr: ToastrService,
        private cloudService: CloudService,
        @Inject(MAT_DIALOG_DATA) public data: { project: CloudProject; serviceId: number, teamMembers: TeamMember[], loadingTeamMembers: boolean}) {

        this.serviceId = this.data.serviceId;
        this.loadingTeamMembers = this.data.loadingTeamMembers;
        this.teamMembers = this.data.teamMembers;
    }

    ngOnInit() {
         this.getMembers();

    }
    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    getMembers() {
        this.loadingTeamMembers = true;
        this.cloudService.getMembers().subscribe(result => {
            this.teamMembers = result.data;
            this.loadingTeamMembers = false;
        }, error => {
            this.loadingTeamMembers = false;
        });
    }

    deleteMember(teamMember: TeamMember) {
        this.loadingTeamMembers = true;
        this.cloudService.deleteMember(teamMember.team_member_id).subscribe(result => {
            this.loadingTeamMembers = false;
            this.getMembers();
        }, error => {
            this.loadingTeamMembers = false;
        });
    }

    inviteMember() {
        this.loadingTeamMembers = true;
        const email = this.emailFormControl.value;
        const role = this.roleFormControl.value;

        this.cloudService.addNewMember(email, role).subscribe(result => {
            setTimeout(() => this.getMembers(), 500);
        }, error => {
            this.loadingTeamMembers = false;
        });
    }

    changeRole(teamMember: TeamMember) {

        this.loadingTeamMembers = true;
        this.cloudService.updateMemberRole(teamMember.team_member_id, teamMember.role).subscribe(result => {
            this.loadingTeamMembers = false;
            this.toastr.success('The role for the user has been changed');
        }, error => {
            this.loadingTeamMembers = false;
        });
    }
}
