import type { ApiClient } from "../client";
import type { ApiMessageDataResponse, ApiMessageResponse, CreateMenuSlotRequest, MenuSlot, MenuSlotsListResponse, MenusListResponse, UpdateMenuSlotRequest } from "../types";
export declare class MenusResource {
    private readonly client;
    constructor(client: ApiClient);
    list(): Promise<MenusListResponse>;
    slots(): Promise<MenuSlotsListResponse>;
    assignSlot(payload: CreateMenuSlotRequest): Promise<ApiMessageDataResponse<MenuSlot>>;
    updateSlot(id: number, payload: UpdateMenuSlotRequest): Promise<ApiMessageDataResponse<MenuSlot>>;
    deleteSlot(id: number): Promise<ApiMessageResponse>;
}
