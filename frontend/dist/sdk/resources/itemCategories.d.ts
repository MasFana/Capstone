import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse, ApiMessageResponse, ItemCategory, LookupListQuery, LookupNameRequest } from "../types";
export declare class ItemCategoriesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: LookupListQuery): Promise<ApiListResponse<ItemCategory>>;
    get(id: number): Promise<ApiDataResponse<ItemCategory>>;
    create(payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemCategory>>;
    update(id: number, payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemCategory>>;
    delete(id: number): Promise<ApiMessageResponse>;
    restore(id: number): Promise<ApiMessageDataResponse<ItemCategory>>;
}
