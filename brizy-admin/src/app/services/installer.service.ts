import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs/internal/Observable';
import { License } from '../interfaces/license.interface';
import { ApiResponse } from '../interfaces/apiResponse.interface';
import { InitData } from '../interfaces/initData.interface';
import { ServiceInfo } from '../interfaces/serviceInfo.interface';
@Injectable({
    providedIn: 'root'
})
export class InstallerService {

    constructor(private http: HttpClient) { }

    creteDb(serviceId: number): Observable<any> {
        return this.http.get(`index.php?m=brizy&action=api&execute=createDb&serviceId=${serviceId}`);
    }

    creteDbUser(serviceId: number): Observable<any> {
        return this.http.get<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=createDbUser&serviceId=${serviceId}`);
    }

    uploadInstallationScript(serviceId: number, options: any = null): Observable<ApiResponse<any>> {
        return this.http.post<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=uploadInstallationScript&serviceId=${serviceId}`, options);
    }

    addCronJob(serviceId: number): Observable<ApiResponse<any>> {
        return this.http.get<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=addCronJob&serviceId=${serviceId}`);
    }

    initData(serviceId: number): Observable<ApiResponse<InitData>> {
        return this.http.get<ApiResponse<InitData>>(`index.php?m=brizy&action=api&execute=initData&serviceId=${serviceId}`);
    }

    serviceInfo(serviceId: number): Observable<ApiResponse<ServiceInfo>> {
        return this.http.get<ApiResponse<InitData>>(`index.php?m=brizy&action=api&execute=serviceInfo&serviceId=${serviceId}`);
    }

    testFtpConnection(serviceId: number, options: any): Observable<ApiResponse<any>> {
        return this.http.post<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=testFtpConnection&serviceId=${serviceId}`, options);
    }

    uploadInstallationScriptFtp(serviceId: number, options: any): Observable<ApiResponse<any>> {
        return this.http.post<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=uploadInstallationScriptFtp&serviceId=${serviceId}`, options);
    }

    runInstallationScriptFtp(serviceId: number): Observable<ApiResponse<any>> {
        return this.http.get<ApiResponse<any>>(`index.php?m=brizy&action=api&execute=runInstallationScriptFtp&serviceId=${serviceId}`);
    }
}
