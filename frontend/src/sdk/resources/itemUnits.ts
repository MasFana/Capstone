import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiListResponse,
  ApiMessageDataResponse,
  ApiMessageResponse,
  ItemUnit,
  LookupListQuery,
  LookupNameRequest
} from "../types";

export class ItemUnitsResource {
  public constructor(private readonly client: ApiClient) {}

  public list(query?: LookupListQuery): Promise<ApiListResponse<ItemUnit>> {
    return this.client.request<ApiListResponse<ItemUnit>>({
      method: "GET",
      path: "/item-units",
      ...(query ? { query: buildLookupQuery(query) } : {})
    });
  }

  public get(id: number): Promise<ApiDataResponse<ItemUnit>> {
    return this.client.request<ApiDataResponse<ItemUnit>>({
      method: "GET",
      path: `/item-units/${id}`
    });
  }

  public create(payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemUnit>> {
    return this.client.request<ApiMessageDataResponse<ItemUnit>>({
      method: "POST",
      path: "/item-units",
      body: payload
    });
  }

  public update(id: number, payload: LookupNameRequest): Promise<ApiMessageDataResponse<ItemUnit>> {
    return this.client.request<ApiMessageDataResponse<ItemUnit>>({
      method: "PUT",
      path: `/item-units/${id}`,
      body: payload
    });
  }

  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/item-units/${id}`
    });
  }

  public restore(id: number): Promise<ApiMessageDataResponse<ItemUnit>> {
    return this.client.request<ApiMessageDataResponse<ItemUnit>>({
      method: "PATCH",
      path: `/item-units/${id}/restore`
    });
  }
}

function buildLookupQuery(query: LookupListQuery): Record<string, string | number> {
  const result: Record<string, string | number> = {};

  if (query.paginate !== undefined) result.paginate = query.paginate ? "true" : "false";
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
