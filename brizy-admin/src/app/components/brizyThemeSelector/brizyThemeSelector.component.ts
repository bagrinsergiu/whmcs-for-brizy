import { ToastrService } from 'ngx-toastr';
import { HttpClient } from '@angular/common/http';
import { Term } from './../../interfaces/term.interface';
import { Demos } from './../../interfaces/demos.interface';
import { ThemeService } from './../../services/theme.service';
import { Component, Input, OnInit } from '@angular/core';
import { AdvancedOptions } from 'src/app/interfaces/advancedOptions';
import { InitData } from 'src/app/interfaces/initData.interface';
import { InstallerService } from 'src/app/services/installer.service';
import { Demo } from 'src/app/interfaces/demo.interface';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { ConfirmModal } from '../confirmModal/confrimModal.component';
import { TranslateService } from '@ngstack/translate';

@Component({
    selector: 'app-brizy-theme-selector',
    templateUrl: './brizyThemeSelector.component.html',
    styleUrls: ['./brizyThemeSelector.component.scss']
})
export class BrizyThemeSelectorComponent implements OnInit {

    @Input() pro: number = 0;
    @Input() lang: string = 'english';
    @Input() productId: number;

    loadingData = true;
    themes: Demos = {
        demos: [] as Demo[],
        terms: [] as Term[],
    };

    filters = {
        type: 0,
        category: "0",
        phrase: ''
    }

    selectedTheme: Demo = null;

    constructor(
        private themeService: ThemeService,
        private modalService: NgbModal,
        private toastr: ToastrService,
        private translate: TranslateService
    ) {
        this.translate.use(this.lang);
    }

    ngOnInit() {
        this.getTemplates();
    }

    getTemplates() {
        this.loadingData = true;
        this.themeService.getAll().subscribe({
            next: (response) => {
                const demos = Object.entries(response.demos).map(([k, v]) => (v));
                const terms = Object.entries(response.terms).map(([k, v]) => (v));
                this.themes.demos = demos as Demo[];
                this.themes.terms = terms as Term[];
                this.loadingData = false;
                this.selectedTheme = this.themes.demos.filter(i => !i.pro)[0];

                this.themeService.getSelectedTemplate().subscribe({
                    next: (response) => {
                        const themeIdFromSession = this.themes.demos.find(demo => demo.id == response.data.themeId);
                        if (themeIdFromSession) {

                            if (!themeIdFromSession.pro  || (themeIdFromSession.pro && this.pro == 1)) {
                                this.selectedTheme = themeIdFromSession;
                            }
                        }

                        this.themeService.setTemplate(this.selectedTheme.id, this.productId).subscribe();
                    }
                });
            },
            error: (error) => {
                this.loadingData = false;
            }
        });

    }

    selectTheme(demo: Demo) {
        this.loadingData = true;
        this.themeService.setTemplate(demo.id, this.productId).subscribe({
            next: (response) => {

                if (demo.pro && response.data.addon_available) {
                    this.toastr.success(this.translate.get('themeSelector.messages.themSelectConfirmation', {name: demo.name}))
                } else {
                    this.toastr.success(this.translate.get('themeSelector.messages.themSelectConfirmation', {name: demo.name}));
                }


                this.selectedTheme = demo;
                this.loadingData = false;
            },
            error: (error) => {
                if (error.status != 403) {
                    this.toastr.error(this.translate.get('themeSelector.messages.themeSelectFailed'));
                }
                this.loadingData = false;
            }

        });
    }

    openPreview(demo: Demo) {
        window.open(demo.url, '_blank');
    }

    get demos() {
        return this.themes.demos.filter(item => {
            let filterStatus = true;

            if (this.filters.type) {
                if (this.filters.type === 1 && !item.pro) {
                    filterStatus = false;
                }

                if (this.filters.type === 2 && item.pro) {
                    filterStatus = false;
                }
            }

            if (parseInt(this.filters.category) > 0) {
               if (!item.terms.includes(this.filters.category))
               {
                    filterStatus = false;
               }
            }

            if (this.filters.phrase) {
                const phrase = this.filters.phrase.toLocaleLowerCase();
                if (!item.name.toLocaleLowerCase().includes(phrase) && !item.keywords.toLocaleLowerCase().includes(phrase)) {
                    filterStatus = false;
                }
            }

            return filterStatus;
        });
    }
}
