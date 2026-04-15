import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiMessageDataResponse,
  ApiMessageResponse,
  CreateDishCompositionRequest,
  DishComposition,
  DishCompositionsListResponse,
  ListDishCompositionsQuery,
  UpdateDishCompositionRequest
} from "../types";

export class DishCompositionsResource {
  public constructor(private readonly client: ApiClient) {}

  public list(query?: ListDishCompositionsQuery): Promise<DishCompositionsListResponse> {
    return this.client.request<DishCompositionsListResponse>({
      method: "GET",
      path: "/dish-compositions",
      ...(query ? { query: buildDishCompositionsQuery(query) } : {})
    });
  }

  public get(id: number): Promise<ApiDataResponse<DishComposition>> {
    return this.client.request<ApiDataResponse<DishComposition>>({
      method: "GET",
      path: `/dish-compositions/${id}`
    });
  }

  public create(payload: CreateDishCompositionRequest): Promise<ApiMessageDataResponse<DishComposition>> {
    return this.client.request<ApiMessageDataResponse<DishComposition>>({
      method: "POST",
      path: "/dish-compositions",
      body: payload
    });
  }

  public update(id: number, payload: UpdateDishCompositionRequest): Promise<ApiMessageDataResponse<DishComposition>> {
    return this.client.request<ApiMessageDataResponse<DishComposition>>({
      method: "PUT",
      path: `/dish-compositions/${id}`,
      body: payload
    });
  }

  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/dish-compositions/${id}`
    });
  }
}

function buildDishCompositionsQuery(query: ListDishCompositionsQuery): Record<string, string | number> {
  const result: Record<string, string | number> = {};

  if (query.page !== undefined) result.page = query.page;
  if (query.perPage !== undefined) result.perPage = query.perPage;
  if (query.dish_id !== undefined) result.dish_id = query.dish_id;
  if (query.item_id !== undefined) result.item_id = query.item_id;
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
