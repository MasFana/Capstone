import type { ApiClient } from "../client";
import type {
  CreateDishRequest,
  Dish,
  DishCreateResponse,
  DishesListResponse,
  ListDishesQuery,
  UpdateDishRequest
} from "../types";

export class DishesResource {
  public constructor(private readonly client: ApiClient) {}

  public list(query?: ListDishesQuery): Promise<DishesListResponse> {
    return this.client.request<DishesListResponse>({
      method: "GET",
      path: "/dishes",
      ...(query ? { query: buildDishesQuery(query) } : {})
    });
  }

  public get(id: number): Promise<{ data: Dish }> {
    return this.client.request<{ data: Dish }>({
      method: "GET",
      path: `/dishes/${id}`
    });
  }

  public create(payload: CreateDishRequest): Promise<DishCreateResponse> {
    return this.client.request<DishCreateResponse>({
      method: "POST",
      path: "/dishes",
      body: payload
    });
  }

  public update(id: number, payload: UpdateDishRequest): Promise<DishCreateResponse> {
    return this.client.request<DishCreateResponse>({
      method: "PUT",
      path: `/dishes/${id}`,
      body: payload
    });
  }
}

function buildDishesQuery(query: ListDishesQuery): Record<string, string | number> {
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
