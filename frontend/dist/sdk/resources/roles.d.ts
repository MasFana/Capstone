import type { ApiDataResponse, Role } from "../types";
import type { ApiClient } from "../client";
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
    list(): Promise<ApiDataResponse<Role[]>>;
}
