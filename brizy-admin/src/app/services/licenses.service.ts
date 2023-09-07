import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs/internal/Observable';
import { License } from '../interfaces/license.interface';
import { ApiResponse } from '../interfaces/apiResponse.interface';
@Injectable({
    providedIn: 'root'
})
export class LicenseService {

    constructor(private http: HttpClient) { }

    getAll(): Observable<ApiResponse<License[]>> {
        return this.http.get<ApiResponse<License[]>>(`addonmodules.php?module=brizy&action=api&execute=getAllLicenses`);
    }

    delete(licenseId: number): Observable<{ data: Array<any> }> {
        return this.http.get<{ data: Array<any> }>(`addonmodules.php?module=brizy&action=api&execute=deleteLicense&license_id=${licenseId}`);
    }

    revoke(licenseId: number): Observable<{ data: Array<any> }> {
        return this.http.get<{ data: Array<any> }>(`addonmodules.php?module=brizy&action=api&execute=revokeLicense&license_id=${licenseId}`);
    }

    disable(licenseId: number): Observable<{ data: Array<any> }> {
        return this.http.get<{ data: Array<any> }>(`addonmodules.php?module=brizy&action=api&execute=disableLicense&license_id=${licenseId}`);
    }

    activate(licenseId: number): Observable<{ data: Array<any> }> {
        return this.http.get<{ data: Array<any> }>(`addonmodules.php?module=brizy&action=api&execute=activateLicense&license_id=${licenseId}`);
    }

    add(licenseString: string): Observable<ApiResponse<any>> {
        return this.http.post<ApiResponse<any>>(`addonmodules.php?module=brizy&action=api&execute=addLicense`, { license: licenseString });
    }
}
