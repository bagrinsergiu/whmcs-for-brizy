import { Template } from 'src/app/interfaces/template.interface';
import { Component, Input, OnInit } from '@angular/core';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { TranslateService } from '@ngstack/translate';
import { AdvancedOptions } from 'src/app/interfaces/advancedOptions';
import { InitData } from 'src/app/interfaces/initData.interface';
import { InstallerService } from 'src/app/services/installer.service';
import { ConfirmModal } from 'src/app/components/confirmModal/confrimModal.component';
import { CloudProject } from 'src/app/interfaces/project.inteface';
import { PublishDialogComponent } from './publishDialog/publishDialog.component';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogService } from '../confirmDialog/confirmdialog.service';
import { MembersDialogComponent } from './membersDialog/membersDialog.component';
import { CloudService } from 'src/app/services/cloud.service';
import { TeamMember } from 'src/app/interfaces/member.interface';
import { RenameDialogComponent } from './renemeDialog/renameDialog.component';
import { AiBuilderDialog } from './aiBuilderDialog/aiBuilderDialog.component';
import { of } from 'rxjs';
import { TemplateSelectorDialog } from './themeSelectorDialog/templateSelectorDialog.component';

@Component({
    selector: 'app-brizy-cloud-manager',
    templateUrl: './cloudManager.component.html',
    styleUrls: ['./cloudManager.component.scss']
})
export class CloudManagerComponent implements OnInit {

    @Input() serviceId: number;
    @Input() deploy: number = 0;

    defaultConfirmModalSettings = { backdrop: false, keyboard: false, centered: true, modalDialogClass: 'panel-primary', windowClass: 'modal whmcs-modal fade in show'};

    public loading = true;
    public loadingTeamMembers = true;
    public projects: CloudProject[] = [];
    public teamMembers: TeamMember[] = [];
    public newProjectName = '';
    public searchQuery = '';

    public templates:  Template[] = [];

    constructor(
        private translate: TranslateService,
        private dialog: MatDialog,
        private confirmService: ConfirmDialogService,
        private cloudService: CloudService
    ) {

    }

    ngOnInit() {

        this.cloudService.setServiceId(this.serviceId ?? 35);
        console.log(`Brizy Cloud Manager S:${this.serviceId}` );
        this.getProjects();
        this.getMembers();
    }

    get membersCount() {
        return this.loadingTeamMembers ? '...' : this.teamMembers.length;
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

    getProjects() {
        this.loading = true;
        this.cloudService.getProjects().subscribe(result => {
            this.projects = result.data.map(project => {
                const thirdPartyDomain = project.domains.find(domain => domain.type === 'third-party' );
                if (thirdPartyDomain) {
                    project.mainDomain = thirdPartyDomain.full_name;
                }

                if (!thirdPartyDomain) {
                    project.mainDomain = project.domains[0].full_name;
                }

                return project;
            }) || [];
            this.loading = false;
        }, error => {
            this.loading = false;
        });
    }

    membersEditor() {
        const dialogRef = this.dialog.open(MembersDialogComponent, {
            width: '50vw',
            minWidth: '360px',
            data: {
                projects: this.projects,
                serviceId: this.serviceId,
                teamMembers: this.teamMembers,
                loadingTeamMembers: this.loadingTeamMembers
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            this.getMembers();
            if (!result) {
                return;
            }

        });
    }

    projectDelete(project: CloudProject) {
        this.confirmService.openConfirmDialog(
            `Are you sure you want to delete this project?<br/><strong>${project.name}</strong>`,
        ).subscribe(
            (proceed) => {
                if (!proceed) {
                    return;
                }
                this.loading = true;
                this.cloudService.deleteProject(project.id).subscribe( (response) => {
                    this.loading = false;
                    this.getProjects();
                }, (error) => {
                    this.loading = false;
                });
            }
        );
    }

    projectEdit(project: CloudProject) {
        this.confirmService.openConfirmDialog(
            `<strong>${project.name}</strong><br/>A new window with the project editor opens<br/>Do you want to continue?`,
        ).subscribe(
            (proceed) => {
                if (!proceed) {
                    return;
                }
                this.loading = true;
                this.cloudService.getProjectLink(project.id).subscribe( (response) => {
                    window.open(response.data.url, '_blank').focus();
                    this.loading = false;
                }, (error) => {
                    this.loading = false;
                });

            }
        );
    }

    projectPreview(project: CloudProject) {
        window.open(project.mainDomain, '_blank').focus();
    }

    projectDownload(project: CloudProject) {
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

    projectRename(project: CloudProject) {
        const dialogRef = this.dialog.open(RenameDialogComponent, {
            data: {
                project: project,
                serviceId: this.serviceId,
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }

        });
    }

    addNewProject(projectName: string) {

        const dialogRef = this.dialog.open(TemplateSelectorDialog, {
            disableClose: true,
            data: {
                templates: this.templates
            }
        });

        dialogRef.afterClosed().subscribe((result: Template) => {
            if (!result) {
                return;
            }

            console.log(result);

            this.confirmService.openConfirmDialog(
                `Are you sure you want to create a project named <strong>"${projectName}"</strong>?`,
            ).subscribe(
                (proceed) => {
                    if (!proceed) {
                        return;
                    }
                    this.loading = true;
                    this.cloudService.addNewProject(projectName, result.project).subscribe( (response) => {
                        console.log(response.data);
                        this.loading = false;
                        this.getProjects();
                    }, (error) => {
                        this.loading = false;
                    });
                }
            );
        });



    }

    projectPublish(project: CloudProject) {

        const dialogRef = this.dialog.open(PublishDialogComponent, {
            minWidth: '360px',
            data: {
                project,
                serviceId: this.serviceId,
                deploy: this.deploy
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }

            this.getProjects();
        });
    }

    addNewProjectAi() {
        const dialogRef = this.dialog.open(AiBuilderDialog, {
            width: '50vw',
            minWidth: '360px',
            disableClose: true,
            data: {
                serviceId: this.serviceId
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }

            this.getProjects();
        });
    }

    openTemplateSelector() {
        const dialogRef = this.dialog.open(TemplateSelectorDialog, {
            disableClose: true,
            data: {
                serviceId: this.serviceId
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }


        });
    }

    get filteredProjects() {
        if (this.searchQuery === '') {
            return this.projects;
        }
        return this.projects.filter(project => project.name.toLowerCase().includes(this.searchQuery.toLowerCase())).sort((a, b) => {
            return a.name.localeCompare(b.name);
        });
    }
}
