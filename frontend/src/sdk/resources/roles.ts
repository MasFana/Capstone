import type { ApiDataResponse, Role } from "../types";
import type { ApiClient } from "../client";

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
  public list(): Promise<ApiDataResponse<Role[]>> {
    return this.client.request<ApiDataResponse<Role[]>>({
      method: "GET",
      path: "/roles"
    });
  }
}
