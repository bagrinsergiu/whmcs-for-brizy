import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs/internal/Observable';
import { License } from '../interfaces/license.interface';
import { ApiResponse } from '../interfaces/apiResponse.interface';
import { CloudProject } from '../interfaces/project.inteface';
import { TeamMember } from '../interfaces/member.interface';
import { environment } from 'src/environments/environment';
@Injectable({
    providedIn: 'root'
})
export class CloudService {

    public members;
    public projects;
    public serviceId = 0;

    public templates: Observable<any>;

    constructor(private http: HttpClient) {

    }

    setServiceId(serviceId: number) {
        this.serviceId = serviceId;
    }


    /* PROJECTS */
    getProjects(): Observable<{data: CloudProject[]}> {
        return this.http.get<{data: CloudProject[]}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getProjects&serviceId=${this.serviceId}`);
    }

    getProjectLink(projectId: number): Observable<{data: any}> {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getProjectLink&projectId=${projectId}&serviceId=${this.serviceId}`);
    }

    addNewProject(projectName: string, templateProjectId = null): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=addNewProject&serviceId=${this.serviceId}`, {projectName, projectId: templateProjectId});
    }

    deleteProject(projectId: number): Observable<{data: any}> {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=deleteProject&projectId=${projectId}&serviceId=${this.serviceId}`);
    }

    renameProject(projectId: number, projectName: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=renameProject&serviceId=${this.serviceId}`, {name: projectName, id: projectId});
    }

    changeProjectDomain(projectId: number, domainId: number, value: string, subdomain: boolean): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=changeProjectDomain&serviceId=${this.serviceId}`,
            {
                projectId,
                domainId,
                value,
                subdomain
            }
        );
    }

    deleteProjectDomain(projectId: number, domainId: number): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=deleteProjectDomain&serviceId=${this.serviceId}`,
            {
                projectId,
                domainId,
            }
        );
    }

    getDownloadLink(projectId: number): Observable<{data: any}> {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getDownloadLink&projectId=${projectId}&serviceId=${this.serviceId}`);
    }


    deployProject(projectId: number): Observable<{data: any}> {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=deployProject&projectId=${projectId}&serviceId=${this.serviceId}`);
    }

    /* USERS */

    getMembers(): Observable<{data: TeamMember[]}> {
        return this.http.get<{data: TeamMember[]}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getMembers&serviceId=${this.serviceId}`);
    }

    addNewMember(email: string, role: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=addNewMember&serviceId=${this.serviceId}`, {email, role});
    }

    deleteMember(teamMemberId: number): Observable<{data: any}> {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=deleteMember&teamMemberId=${teamMemberId}&serviceId=${this.serviceId}`);
    }

    updateMemberRole(teamMemberId: number, role: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=changeMemberRole&serviceId=${this.serviceId}`, {teamMemberId, role});
    }

    /* AI BUILDER */


    getIdeas(description: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getIdeas&serviceId=${this.serviceId}`,
            {
                description,
            }
        );
    }

    getBusiness(phrase: string, lang = 'PL'): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getBusiness&serviceId=${this.serviceId}`,
            {
                phrase,
                lang
            }
        );
    }

    buildWebsite(
            company: string,
            lang: string,
            industry: string,
            description: string = '',
            phone: string = '',
            email: string = '',
            gId: string = null

    ): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=buildWebsite&serviceId=${this.serviceId}`,
            {
                company,
                lang,
                industry,
                description,
                phone,
                email,
                gId,
            }
        );
    }

    buildWebsitePages(id: string, pages = ['home', 'about-us', 'contact', 'services', 'review']): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=buildWebsitePages&serviceId=${this.serviceId}`, {id, pages} );
    }

    finishWebsite(id: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=finishWebsite&serviceId=${this.serviceId}`, {id} );
    }

    authorizeWebsite(id: string): Observable<{data: any}> {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=authorizeWebsite&serviceId=${this.serviceId}`, {id} );
    }

    getAiWebsiteData(id: string): Observable<{data: any}>{
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getAiWebsiteData&serviceId=${this.serviceId}`, {id} );
    }

    /* Templates */

    getTemplates(categoryId: number = null) {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=template&execute=getTemplates`, {category: categoryId} );
    }

    getTemplatesCategories() {
        return this.http.get<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=cloud&execute=getTemplatesCategories`);
    }

    setTemplate(i: number, templateProjectId: number) {
        return this.http.post<{data: any}>(`${environment.apiUrl}index.php?m=brizy&action=template&execute=setTemplate`, {i, templateId: templateProjectId} );
    }

}
