import { TemplateCategory } from "./templateCategory";

export interface Template {
    'id': number,
    'title': string,
    'slug': string,
    'featured_image': string,
    'categories': TemplateCategory[],
    'project': number,
    'preview_url': string,

}
