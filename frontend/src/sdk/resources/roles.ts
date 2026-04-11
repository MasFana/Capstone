import type { ApiClient } from "../client";
import type { ApiListResponse, Role, RoleListQuery } from "../types";

/**
 * Role lookup endpoints.
 */
export class RolesResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Lists all available roles.
   *
   * HTTP: `GET /api/v1/roles`
   * Access: `admin` only
   */
  public list(query?: RoleListQuery): Promise<ApiListResponse<Role>> {
    return this.client.request<ApiListResponse<Role>>({
      method: "GET",
      path: "/roles",
      ...(query ? { query: buildRoleQuery(query) } : {})
    });
  }
}

function buildRoleQuery(query: RoleListQuery): Record<string, string | number> {
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
