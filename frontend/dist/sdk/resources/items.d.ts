import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse, ApiMessageResponse, CreateItemRequest, Item, ListItemsQuery, UpdateItemRequest } from "../types";
/**
 * Item master endpoints.
 */
export declare class ItemsResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Lists items with pagination, filtering, and search.
     *
     * HTTP: `GET /api/v1/items`
     * Access: `admin`, `gudang`
     */
    list(query?: ListItemsQuery): Promise<ApiListResponse<Item>>;
    /**
     * Returns a single item by identifier.
     *
     * HTTP: `GET /api/v1/items/{id}`
     * Access: `admin`, `gudang`
     */
    get(id: number): Promise<ApiDataResponse<Item>>;
    /**
     * Creates a new item.
     *
     * HTTP: `POST /api/v1/items`
     * Access: `admin`, `gudang`
     */
    create(payload: CreateItemRequest): Promise<ApiMessageDataResponse<Item>>;
    /**
     * Updates an existing item using the backend's partial-update semantics.
     *
     * HTTP: `PUT /api/v1/items/{id}`
     * Access: `admin`, `gudang`
     */
    update(id: number, payload: UpdateItemRequest): Promise<ApiMessageDataResponse<Item>>;
    /**
     * Soft-deletes an item.
     *
     * HTTP: `DELETE /api/v1/items/{id}`
     * Access: `admin` only
     */
    delete(id: number): Promise<ApiMessageResponse>;
    /**
     * Restores a soft-deleted item.
     *
     * Idempotent: if the item is already active, returns 200 with current data.
     * Returns 400 if an active item with the same name already exists.
     * Returns 400 if the referenced category or units are no longer active.
     * Returns 404 if the item does not exist at all.
     *
     * HTTP: `PATCH /api/v1/items/{id}/restore`
     * Access: `admin` only
     */
    restore(id: number): Promise<ApiMessageDataResponse<Item>>;
}
