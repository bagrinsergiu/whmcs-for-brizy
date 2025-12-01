import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { ToastrService } from 'ngx-toastr';
import { Template } from 'src/app/interfaces/template.interface';
import { TemplateCategory } from 'src/app/interfaces/templateCategory';

import { CloudService } from 'src/app/services/cloud.service';

@Component({
    selector: 'app-cloud-theme-selector-dialog',
    templateUrl: 'templateSelectorDialog.component.html',
    styleUrls: ['./templateSelectorDialog.component.scss']
}) export class TemplateSelectorDialog implements OnInit {


    loadingCategories = false;
    loadingTemplates = false;
    loading = false;
    categories = [];
    selectedCategory: TemplateCategory = null;
    templates: Template[] = [];

    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
        public dialogRef: MatDialogRef<TemplateSelectorDialog>,
        @Inject(MAT_DIALOG_DATA) public data: {
            templates: Template[],
            loadingTemplates: boolean
        }) {
    }



    ngOnInit() {
        if(this.data.templates && this.data.templates.length === 0) {
            this.getTemplates();
        }


    }

    selectCategory(category: TemplateCategory) {
        this.selectedCategory = category;

    }

    getTemplates(categoryId: number = null) {
        this.loadingTemplates = true;
        this.cloudService.getTemplates(categoryId).subscribe((response) => {
            this.loadingTemplates = false;
            this.templates = response.data;
            this.getCategoriesFromTemplates();
            this.data.templates = this.templates;

        }, (errorResponse) => {
            this.loadingTemplates = false;
        });
    }

    cancel() {
        this.dialogRef.close(false);
    }

    ok() {
        this.dialogRef.close(true);
    }


    getCategoriesFromTemplates() {
        let categories = [];
        this.templates.forEach(t => {
            categories = categories.concat(t.categories);
        });

        this.categories = Array.from(
            new Map(categories.map(cat => [cat.slug, cat])).values()
          ).sort((a, b) => a.title.localeCompare(b.title));

        this.selectedCategory = this.categories[0];

    }

    previewTemplate(template: Template) {
        window.open(template.preview_url, '_blank').focus();
    }

    selectTemplate(template: Template) {
        this.dialogRef.close(template);
    }

    get filterdTemplates(){
        if(!this.selectCategory) {
            this.templates;
        }

        return  this.templates.filter(template => {
            return template.categories.find(tc => tc.slug === this.selectedCategory.slug);
        });
    }
}
