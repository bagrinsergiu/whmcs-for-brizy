export interface License {
    id: string;
    license: string;
    user_id: number;
    service_id: number;
    created_at: string;
    updated_at: string;
    clientData?: Array<any>;
    serviceData?: Array<any>;
}
