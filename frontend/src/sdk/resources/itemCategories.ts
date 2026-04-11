import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiListResponse,
  ApiMessageDataResponse,
  ApiMessageResponse,
  ItemCategory,
  LookupListQuery,
  LookupNameRequest
} from "../types";

export class ItemCategoriesResource {
  public constructor(private readonly client: ApiClient) {}

  public list(query?: LookupListQuery): Promise<ApiListResponse<ItemCategory>> {
    return this.client.request<ApiListResponse<ItemCategory>>({
      method: "GET",
      path: "/item-categories",
      ...(query ? { query: buildLookupQuery(query) } : {})
    });
  }

  public get(id: number): Promise<ApiDataResponse<ItemCategory>> {
    return this.client.request<ApiDataResponse<ItemCategory>>({
      method: "GET",
      path: `/item-categories/${id}`
    });
  }

  public create(payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemCategory>> {
    return this.client.request<ApiMessageDataResponse<ItemCategory>>({
      method: "POST",
      path: "/item-categories",
      body: payload
    });
  }

  public update(id: number, payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemCategory>> {
    return this.client.request<ApiMessageDataResponse<ItemCategory>>({
      method: "PUT",
      path: `/item-categories/${id}`,
      body: payload
    });
  }

  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/item-categories/${id}`
    });
  }

  public restore(id: number): Promise<ApiMessageDataResponse<ItemCategory>> {
    return this.client.request<ApiMessageDataResponse<ItemCategory>>({
      method: "PATCH",
      path: `/item-categories/${id}/restore`
    });
  }
}

function buildLookupQuery(query: LookupListQuery): Record<string, string | number> {
  const result: Record<string, string | number> = {};

  if (query.page !== undefined) result.page = query.page;
  if (query.perPage !== undefined) result.perPage = query.perPage;
  if (query.q !== undefined) result.q = query.q;
  if (query.search !== undefined) result.search = query.search;
  if (query.sortBy !== undefined) result.sortBy = query.sortBy;
  if (query.sortDir !== undefined) result.sortDir = query.sortDir;
  if (query.created_at_from !== undefined) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== undefined) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== undefined) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== undefined) result.updated_at_to = query.updated_at_to;

  return result;
}
