import { ToastrService } from 'ngx-toastr';
import { HttpClient } from '@angular/common/http';
import { Term } from '../../interfaces/term.interface';
import { Demos } from '../../interfaces/demos.interface';
import { ThemeService } from '../../services/theme.service';
import { Component, Inject, Input, OnInit } from '@angular/core';
import { AdvancedOptions } from 'src/app/interfaces/advancedOptions';
import { InitData } from 'src/app/interfaces/initData.interface';
import { InstallerService } from 'src/app/services/installer.service';
import { Demo } from 'src/app/interfaces/demo.interface';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { ConfirmModal } from '../confirmModal/confrimModal.component';
import { TranslateService } from '@ngstack/translate';
import { environment } from 'src/environments/environment';
import { Template } from 'src/app/interfaces/template.interface';
import { MAT_DIALOG_DATA, MatDialog } from '@angular/material/dialog';
import { CloudService } from 'src/app/services/cloud.service';
import { TemplateSelectorDialog } from '../cloudManager/themeSelectorDialog/templateSelectorDialog.component';
import { TemplateCategory } from 'src/app/interfaces/templateCategory';

@Component({
    selector: 'app-brizy-cloud-template-selector',
    templateUrl: './brizyCloudTemplateSelector.component.html',
    styleUrls: ['./brizyCloudTemplateSelector.component.scss']
})
export class BrizyCloudTemplateSelectorComponent implements OnInit {

    @Input() pro: number = 0;
    @Input() lang: string = 'english';
    @Input() productId: number;
    @Input() i: number;
    @Input() selectedTemplateId: number;

    loadingCategories = false;
    loadingTemplates = false;
    loading = false;
    categories = [];
    selectedCategory: TemplateCategory = null;
    templates: Template[] = [];
    selectedTemplate: Template = null;


    constructor(
        private dialog: MatDialog,
        private cloudService: CloudService,
        private toastr: ToastrService,
       ) {
    }



    ngOnInit() {
        this.getTemplates();
        console.log(this.selectedTemplateId);
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
            if (this.selectedTemplateId) {
                this.selectedTemplate = this.templates.find(t => t.project == this.selectedTemplateId) || this.templates[0];

                setTimeout(() => {
                    const selectedTemplateIdHTML = 'template-id-'+this.selectedTemplate.id;
                    document.getElementById(selectedTemplateIdHTML).scrollIntoView({ behavior: 'smooth' });
                }, 700);

            } else {
                this.selectedTemplate = this.templates[0];
            }

        }, (errorResponse) => {
            this.loadingTemplates = false;
        });
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
        this.loadingTemplates = true;
        this.cloudService.setTemplate(this.i, template.project).subscribe((response) => {
            this.loadingTemplates = false;
            this.selectedTemplate = template;
        }, (errorResponse) => {
            this.loadingTemplates = false;
        });
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
