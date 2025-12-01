import { CloudService } from 'src/app/services/cloud.service';
import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { INDUSTRIES } from 'src/app/data/industries';
import { Observable, catchError, concatMap, debounceTime, distinctUntilChanged, filter, map, of, switchMap, tap } from 'rxjs';
import { FormControl } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AiProjectEditDialog } from './aiProjectEditDialog/aiProjectEditDialog.component';

import { AiProjectsLocalStorageService } from 'src/app/services/aiProjectsLocalStorage.service';
import { LANGUAGES } from 'src/app/data/language';

@Component({
    selector: 'app-cloud-ai-builder-dialog',
    templateUrl: 'aiBuilderDialog.component.html',
    styleUrls: ['./aiBuilderDialog.component.scss']
}) export class AiBuilderDialog implements OnInit  {


    offerIdeasStatus = false;
    offerIdeasDescription = '';
    ideasLoading = false;
    ideas = [];

    language = 'en'
    businessName:  any = '';
    industry = INDUSTRIES[0];
    description = '';
    phone = '';
    email = '';

    aiProjectId = null;
    buildingWebsite = false;
    aiProjectInterval = null;
    aiProjectBuildingText = '';

    pages = ['home', 'about-us', 'contact', 'services', 'review'];

    searchControl = new FormControl();
    places: Observable<any> = of([]);
    isLoading = false;
    selectedPlace = null;

    industries = INDUSTRIES;
    languages = LANGUAGES;

    constructor(
        private dialog: MatDialog,
        public dialogRef: MatDialogRef<AiBuilderDialog>,
        private cloudService: CloudService,
        private aiProjectsLocalStorage: AiProjectsLocalStorageService,
        @Inject(MAT_DIALOG_DATA) public data: {serviceId: number}) { }


    ngOnInit() {

        this.places = this.searchControl.valueChanges.pipe(
            filter(res => {
                return res !== null && res.length >= 3
            }),
            debounceTime(300),
            distinctUntilChanged(),
            tap(() => this.isLoading = true),
            switchMap(value =>
                value
                    ? this.cloudService.getBusiness(value, this.language).pipe(
                        map(res => {
                            if (Array.isArray(res.data)) {

                                return res.data.map((item: any) => ({
                                    placeId: item.placeId,
                                    text: item.text,
                                    secondaryText: item.secondaryText
                            }));
                            } else {
                                throw new Error('Wrong request data');
                            }
                            }),
                        catchError(err => {
                            return of([]);
                        })
                    )
                    : of([])
                ),
            tap(() => {
                this.isLoading = false;
                this.selectedPlace = null;
            })
        );
    }


    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }

    offerIdeas() {
        this.offerIdeasStatus = true;
    }

    offerIdeasSubmit() {
        this.ideasLoading = true;
        this.cloudService.getIdeas(this.offerIdeasDescription).subscribe(
            (result) => {
                this.ideas = result.data;
                this.ideasLoading = false;
            },
            (error) => {
                this.ideasLoading = false;
            }
        )
    }

    selectIdea(idea: string) {
        this.offerIdeasStatus = false;
        this.selectedPlace = null;
        this.businessName = idea + '';

    }

    buildWebsite() {
        this.buildingWebsite = true;
        this.aiProjectBuildingText = 'We are just creating your website...';

        this.cloudService.buildWebsite(
            this.businessNameForApi,
            this.language,
            this.industry,
            this.description,
            this.phone,
            this.email,
            this.selectedPlace?.placeId ?? null
        ).subscribe(
            (result) => {
                this.aiProjectId = result.data.id;

                this.aiProjectBuildingText = 'Finalizing....';

                setTimeout(() => {
                   this.aiProjectBuildingText = 'Almost ready....';
                }, 1000)

                setTimeout(() => {
                    this.openAiProjectEditDialog(this.businessNameForApi, result.data.id, result.data.url);
                    const aiProject =  {
                        title: this.businessNameForApi,
                        id: result.data.id,
                        url: result.data.url,
                    }

                    this.aiProjectsLocalStorage.add(aiProject);
                    this.buildingWebsite = false;
                }, 2000)
            },
            (error) => {
                this.buildingWebsite = false;
            }
        )
    }





    onPlaceSelected(event: any) {
        this.selectedPlace = event.option.value;
    }

    autocompleteDisplayFn(place: any): string {
        return place?.text || place || '';
    }

    openAiProjectEditDialog(title: string,  id: string, url: string) {
        const dialogRef = this.dialog.open(AiProjectEditDialog, {
            disableClose: true,
            data: {
                title,
                id,
                url
            }
        });

        dialogRef.afterClosed().subscribe(result => {
            if (!result) {
                return;
            }
            this.dialogRef.close(true);
        });
    }

    removeAiProjectToStorage(id: string) {
        this.aiProjectsLocalStorage.remove(id);
    }



    get aiLocalProjects() {
        return this.aiProjectsLocalStorage.get() ?? [];
    }
    get businessNameForApi() {
        return this.selectedPlace?.text ?? this.businessName;
    }

    get steps() {
        return {
            name: {
                completed: this.businessNameForApi.length > 3,
                valid: false
            },
            industry: {
                completed: this.industry.length,
            },
            info: {
                completed: true,
            }

        }
    }

}
