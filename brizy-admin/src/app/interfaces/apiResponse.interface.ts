
export interface ApiResponse<Type= any> {
    status: number;
    data: Type | any | {error: {message: string}};
}
