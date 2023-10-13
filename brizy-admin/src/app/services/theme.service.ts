import { Demos } from './../interfaces/demos.interface';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs/internal/Observable';
import { License } from '../interfaces/license.interface';
import { ApiResponse } from '../interfaces/apiResponse.interface';
import { InitData } from '../interfaces/initData.interface';
@Injectable({
    providedIn: 'root'
})
export class ThemeService {

    constructor(private http: HttpClient) { }

    getAll(): Observable<any> {
        return this.http.get<any>(`index.php?m=brizy&action=template&execute=getDemos`);
    }

    setTemplate(themeId: number, productId: number): Observable<ApiResponse<{pro: boolean; name: string; id: number; addon_available: boolean; product_pro: boolean}>> {
        return this.http.get<ApiResponse<{pro: boolean; name: string; id: number; addon_available: boolean; product_pro: boolean}>>(`index.php?m=brizy&action=template&execute=setInstallerTemplate&themeId=${themeId}&productId=${productId}`);
    }

    getSelectedTemplate(): Observable<ApiResponse<{themeId: number}>> {
        return this.http.get<ApiResponse<{themeId: number}>>(`index.php?m=brizy&action=template&execute=getInstallerTemplate`);
    }
}
