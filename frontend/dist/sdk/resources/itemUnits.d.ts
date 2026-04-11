import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse, ApiMessageResponse, ItemUnit, LookupListQuery, LookupNameRequest } from "../types";
export declare class ItemUnitsResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: LookupListQuery): Promise<ApiListResponse<ItemUnit>>;
    get(id: number): Promise<ApiDataResponse<ItemUnit>>;
    create(payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemUnit>>;
    update(id: number, payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemUnit>>;
    delete(id: number): Promise<ApiMessageResponse>;
    restore(id: number): Promise<ApiMessageDataResponse<ItemUnit>>;
}
