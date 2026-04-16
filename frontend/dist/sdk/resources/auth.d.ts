import type { ApiDataResponse, ApiMessageResponse, LoginRequest, LoginResponse, SelfServiceChangePasswordRequest, User } from "../types";
import type { ApiClient } from "../client";
/**
 * Auth endpoints for login and bearer-backed session inspection.
 */
export declare class AuthResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Logs a user in.
     *
     * HTTP: `POST /api/v1/auth/login`
     * Access: public
     */
    login(payload: LoginRequest): Promise<LoginResponse>;
    /**
     * Returns the current authenticated user.
     *
     * HTTP: `GET /api/v1/auth/me`
     * Access: authenticated `admin`, `dapur`, `gudang`
     */
    me(): Promise<ApiDataResponse<User>>;
    /**
     * Revokes the current access token.
     *
     * HTTP: `POST /api/v1/auth/logout`
     * Access: authenticated `admin`, `dapur`, `gudang`
     */
    logout(): Promise<ApiMessageResponse>;
    /**
     * Changes the current authenticated user's password.
     *
     * HTTP: `PATCH /api/v1/auth/password`
     * Access: authenticated `admin`, `dapur`, `gudang`
     */
    changePassword(payload: SelfServiceChangePasswordRequest): Promise<ApiMessageResponse>;
}
