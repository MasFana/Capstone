import type { ApiClient } from "../client";
import type { ApiListResponse, Role, RoleListQuery } from "../types";
/**
 * Role lookup endpoints.
 */
export declare class RolesResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Lists all available roles.
     *
     * HTTP: `GET /api/v1/roles`
     * Access: `admin` only
     */
    list(query?: RoleListQuery): Promise<ApiListResponse<Role>>;
}
