import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiListResponse,
  ApiMessageDataResponse,
  ApiMessageResponse,
  CreateItemRequest,
  Item,
  ListItemsQuery,
  UpdateItemRequest
} from "../types";

/**
 * Item master endpoints.
 */
export class ItemsResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Lists items with pagination, filtering, and search.
   *
   * HTTP: `GET /api/v1/items`
   * Access: `admin`, `gudang`
   */
  public list(query?: ListItemsQuery): Promise<ApiListResponse<Item>> {
    return this.client.request<ApiListResponse<Item>>({
      method: "GET",
      path: "/items",
      ...(query ? { query: buildItemsQuery(query) } : {})
    });
  }

  /**
   * Returns a single item by identifier.
   *
   * HTTP: `GET /api/v1/items/{id}`
   * Access: `admin`, `gudang`
   */
  public get(id: number): Promise<ApiDataResponse<Item>> {
    return this.client.request<ApiDataResponse<Item>>({
      method: "GET",
      path: `/items/${id}`
    });
  }

  /**
   * Creates a new item.
   *
   * HTTP: `POST /api/v1/items`
   * Access: `admin`, `gudang`
   */
  public create(payload: CreateItemRequest): Promise<ApiMessageDataResponse<Item>> {
    return this.client.request<ApiMessageDataResponse<Item>>({
      method: "POST",
      path: "/items",
      body: payload
    });
  }

  /**
   * Updates an existing item using the backend's partial-update semantics.
   *
   * HTTP: `PUT /api/v1/items/{id}`
   * Access: `admin`, `gudang`
   */
  public update(id: number, payload: UpdateItemRequest): Promise<ApiMessageDataResponse<Item>> {
    return this.client.request<ApiMessageDataResponse<Item>>({
      method: "PUT",
      path: `/items/${id}`,
      body: payload
    });
  }

  /**
   * Soft-deletes an item.
   *
   * HTTP: `DELETE /api/v1/items/{id}`
   * Access: `admin` only
   */
  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/items/${id}`
    });
  }
}

function buildItemsQuery(query: ListItemsQuery): Record<string, string | number | boolean> {
  const result: Record<string, string | number | boolean> = {};

  if (query.page !== undefined) {
    result.page = query.page;
  }

  if (query.perPage !== undefined) {
    result.perPage = query.perPage;
  }

  if (query.item_category_id !== undefined) {
    result.item_category_id = query.item_category_id;
  }

  if (query.is_active !== undefined) {
    result.is_active = query.is_active;
  }

  if (query.q !== undefined) {
    result.q = query.q;
  }

  return result;
}
