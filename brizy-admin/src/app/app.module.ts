import { provideNgxWebstorage } from 'ngx-webstorage';

import { BrowserModule } from '@angular/platform-browser';
import { ApplicationRef, APP_INITIALIZER, CUSTOM_ELEMENTS_SCHEMA, Injector, NgModule } from '@angular/core';
import { AppComponent } from './app.component';
import { RouterModule } from '@angular/router';

import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { HashLocationStrategy, LocationStrategy } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import { LicensesModule } from './modules/licenses/licenses.module';
import { AppRoutingModule } from './app-routing.module';

import { createCustomElement } from '@angular/elements';
import { InstallerComponent } from './components/installer/installer.component';
import { BrizyThemeSelectorComponent } from './components/brizyThemeSelector/brizyThemeSelector.component';

import { NgbPaginationModule, NgbAlertModule, NgbDropdownModule } from '@ng-bootstrap/ng-bootstrap';
import { ErrorInterceptor } from './interceptors/error.interceptor';
import { ToastrModule } from 'ngx-toastr';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { TranslateModule, TranslateService } from '@ngstack/translate';
import { environment } from 'src/environments/environment';
import { CloudManagerComponent } from './components/cloudManager/cloudManager.component';

import { MatMenuModule } from '@angular/material/menu';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatBadgeModule } from '@angular/material/badge';
import { MatDialogModule } from '@angular/material/dialog';
import { MatTabsModule } from '@angular/material/tabs';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatStepperModule } from '@angular/material/stepper';
import { MatAutocompleteModule } from '@angular/material/autocomplete';

import { PublishDialogComponent } from './components/cloudManager/publishDialog/publishDialog.component';
import { ConfirmDialogComponent } from './components/confirmDialog/confirmDialog.component';
import { MembersDialogComponent } from './components/cloudManager/membersDialog/membersDialog.component';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { RenameDialogComponent } from './components/cloudManager/renemeDialog/renameDialog.component';
import { DomainEditDialog } from './components/cloudManager/domainEditDialog/domainEditDialog.component';
import { AiBuilderDialog } from './components/cloudManager/aiBuilderDialog/aiBuilderDialog.component';
import { AiProjectEditDialog } from './components/cloudManager/aiBuilderDialog/aiProjectEditDialog/aiProjectEditDialog.component';
import { TemplateSelectorDialog} from './components/cloudManager/themeSelectorDialog/templateSelectorDialog.component';
import { BrizyCloudTemplateSelectorComponent} from './components/brizyCloudTemplateSelector/brizyCloudTemplateSelector.component';
import { DeployProjectDialog } from './components/cloudManager/deployDialog/deployDialog.component';


export function setupTranslateService(service: TranslateService) {
    return () => service.use('english');
}

const bootstrap = [];
const adminModuleHrefPart = 'addonmodules';

@NgModule({
    declarations: [
        AppComponent,
        InstallerComponent,
        BrizyThemeSelectorComponent,
        CloudManagerComponent,
        PublishDialogComponent,
        ConfirmDialogComponent,
        MembersDialogComponent,
        RenameDialogComponent,
        DomainEditDialog,
        AiBuilderDialog,
        AiProjectEditDialog,
        TemplateSelectorDialog,
        BrizyCloudTemplateSelectorComponent,
        DeployProjectDialog

    ],
    imports: [
        BrowserModule,
        FormsModule,
        HttpClientModule,
        LicensesModule,
        AppRoutingModule,
        RouterModule,
        NgbAlertModule,
        BrowserAnimationsModule,
        ToastrModule.forRoot(),
        TranslateModule.forRoot({
            debugMode: false,
            disableCache: true,
            activeLang: 'english',
            // translatePaths: ['js'],
            translationRoot: environment.i18nPath

        }),

        ReactiveFormsModule,
        MatMenuModule,
        MatIconModule,
        MatButtonModule,
        MatBadgeModule,
        MatDialogModule,
        MatInputModule,
        MatSelectModule,
        MatProgressSpinnerModule,
        MatTabsModule,
        MatProgressBarModule,
        MatStepperModule,
        MatAutocompleteModule
    ],
    exports: [
        ReactiveFormsModule,
        FormsModule,
        MatMenuModule,
        MatIconModule,
        MatButtonModule,
        MatBadgeModule,
        MatDialogModule,
        MatInputModule,
        MatSelectModule,
        MatProgressSpinnerModule,
        MatProgressBarModule,
        MatStepperModule,
        MatAutocompleteModule
    ],
    schemas: [CUSTOM_ELEMENTS_SCHEMA],
    providers: [
        Location,
        { provide: LocationStrategy, useClass: HashLocationStrategy },
        {
            provide: HTTP_INTERCEPTORS,
            useClass: ErrorInterceptor,
            multi: true
        },
        {
            provide: APP_INITIALIZER,
            useFactory: setupTranslateService,
            deps: [TranslateService],
            multi: true
        },
        provideNgxWebstorage(),
    ],
    bootstrap: bootstrap


})
export class AppModule {

    constructor(private injector: Injector) {

        if (!customElements.get('app-brizy-installer')) {
            const elInstaller = createCustomElement(InstallerComponent, { injector });
            setTimeout(()=>{
                customElements.get('app-brizy-installer') || customElements.define('app-brizy-installer', elInstaller);
            }, 500);
        }
        if (!customElements.get('app-brizy-cloud-manager')) {
            const elCloudManager = createCustomElement(CloudManagerComponent, { injector });
            setTimeout(()=>{
                customElements.get('app-brizy-cloud-manager') || customElements.define('app-brizy-cloud-manager', elCloudManager);
            }, 500);
        }

        if (!customElements.get('app-brizy-theme-selector')) {
            const elThemeSelector = createCustomElement(BrizyThemeSelectorComponent, { injector });
            customElements.define('app-brizy-theme-selector', elThemeSelector);
        }

        if (!customElements.get('app-brizy-cloud-theme-selector')) {
            const elThemeSelector = createCustomElement(BrizyCloudTemplateSelectorComponent, { injector });
            customElements.define('app-brizy-cloud-theme-selector', elThemeSelector);
        }
    }

    ngDoBootstrap(appRef: ApplicationRef) {
        if (window.location.href.includes('addonmodules') || window.location.href.includes('localhost')) {
            appRef.bootstrap(AppComponent);
        }
    }


}
