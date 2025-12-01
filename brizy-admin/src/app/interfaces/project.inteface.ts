import { CloudProjectDomain } from "./cloudProjectDomain.interface";

export interface CloudProject {
    id: number,
    site_title: string,
    status: string,
    name: string,
    url: string,
    deployment_status: 'string',
    processing?: boolean,
    domains: CloudProjectDomain[],
    preview_domain: string
    mainDomain?: string
}
