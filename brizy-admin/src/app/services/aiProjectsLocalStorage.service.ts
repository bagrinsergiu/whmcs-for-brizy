import { Injectable } from '@angular/core';
import { LocalStorageService } from 'ngx-webstorage';
@Injectable({
    providedIn: 'root'
})
export class AiProjectsLocalStorageService {
    private aiProjectsLocalStorage = 'brizy_ai_projects'
    constructor(
        private localStorage:LocalStorageService,
    ) {}


    add(aiProject: {id: string; title: string; url: string}){
        let aiProjects = this.localStorage.retrieve(this.aiProjectsLocalStorage) ?? [];
        aiProjects.push(aiProject);
        this.localStorage.store(this.aiProjectsLocalStorage, aiProjects);
    }

    remove(id) {
        let aiProjects = this.localStorage.retrieve(this.aiProjectsLocalStorage) ?? [];
        aiProjects.splice(aiProjects.findIndex(function(i){
            return i.id === id;
        }), 1);

        this.localStorage.store(this.aiProjectsLocalStorage, aiProjects);
    }

    get() {
        return this.localStorage.retrieve(this.aiProjectsLocalStorage) ?? [];
    }
}
