
import { BrowserModule } from '@angular/platform-browser';
import { ApplicationRef, APP_INITIALIZER, CUSTOM_ELEMENTS_SCHEMA, Injector, NgModule } from '@angular/core';
import { AppComponent } from './app.component';
import { RouterModule } from '@angular/router';

import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { HashLocationStrategy, LocationStrategy } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { LicensesModule } from './modules/licenses/licenses.module';
import { AppRoutingModule } from './app-routing.module';

import { createCustomElement } from '@angular/elements';
import { InstallerComponent } from './components/installer/installer.component';
import { BrizyThemeSelectorComponent } from './components/brizyThemeSelector/brizyThemeSelector.component';




import { NgbPaginationModule, NgbAlertModule } from '@ng-bootstrap/ng-bootstrap';
import { ErrorInterceptor } from './interceptors/error.interceptor';
import { ToastrModule } from 'ngx-toastr';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { TranslateModule, TranslateService } from '@ngstack/translate';
import { environment } from 'src/environments/environment';


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
    ],
    exports: [
        FormsModule
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
        }
    ],
    bootstrap: bootstrap


})
export class AppModule {

    constructor(private injector: Injector) {
        const elInstaller = createCustomElement(InstallerComponent, { injector });
        setTimeout(()=>{
            customElements.define('app-brizy-installer', elInstaller);
        }, 500);

        const elThemeSelector = createCustomElement(BrizyThemeSelectorComponent, { injector });
        customElements.define('app-brizy-theme-selector', elThemeSelector);
    }
    ngDoBootstrap(appRef: ApplicationRef) {
        if (window.location.href.includes('addonmodules') || window.location.href.includes('localhost')) {
            appRef.bootstrap(AppComponent);
        }
    }


}
