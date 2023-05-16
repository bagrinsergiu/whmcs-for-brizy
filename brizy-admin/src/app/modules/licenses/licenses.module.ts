import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LicensesListComponent } from './list/list.component';
import { LoaderComponent } from 'src/app/components/loader/loader.component';
import { FormsModule } from '@angular/forms';

@NgModule({
    imports: [
        CommonModule,
        FormsModule,
    ],
    declarations: [LicensesListComponent, LoaderComponent],
})
export class LicensesModule { }
