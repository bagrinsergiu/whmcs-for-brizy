import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { LoaderComponent } from './components/loader/loader.component';
import { LicensesListComponent } from './modules/licenses/list/list.component';

const routes: Routes = [
  { path: '**', component: LicensesListComponent }
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {})
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }
