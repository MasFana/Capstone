import type { ApiClient } from "../client";
import type { ApiListResponse, LookupListQuery, MealTime } from "../types";

export class MealTimesResource {
  public constructor(private readonly client: ApiClient) {}

  public list(query?: LookupListQuery): Promise<ApiListResponse<MealTime>> {
    return this.client.request<ApiListResponse<MealTime>>({
      method: "GET",
      path: "/meal-times",
      ...(query ? { query: buildLookupQuery(query) } : {})
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
