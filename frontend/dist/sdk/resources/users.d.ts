import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiMessageDataResponse, ApiMessageResponse, ChangePasswordRequest, CreateUserRequest, UpdateUserRequest, User } from "../types";
/**
 * User management endpoints.
 */
export declare class UsersResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Lists all users.
     *
     * HTTP: `GET /api/v1/users`
     * Access: `admin` only
     */
    list(): Promise<ApiDataResponse<User[]>>;
    /**
     * Returns a single user by identifier.
     *
     * HTTP: `GET /api/v1/users/{id}`
     * Access: `admin` only
     */
    get(id: number): Promise<ApiDataResponse<User>>;
    /**
     * Creates a new user.
     *
     * HTTP: `POST /api/v1/users`
     * Access: `admin` only
     */
    create(payload: CreateUserRequest): Promise<ApiMessageDataResponse<User>>;
    /**
     * Updates a user profile and role assignment.
     *
     * HTTP: `PUT /api/v1/users/{id}`
     * Access: `admin` only
     */
    update(id: number, payload: UpdateUserRequest): Promise<ApiMessageDataResponse<User>>;
    /**
     * Activates a user account.
     *
     * HTTP: `PATCH /api/v1/users/{id}/activate`
     * Access: `admin` only
     */
    activate(id: number): Promise<ApiMessageDataResponse<User>>;
    /**
     * Deactivates a user account.
     *
     * HTTP: `PATCH /api/v1/users/{id}/deactivate`
     * Access: `admin` only
     */
    deactivate(id: number): Promise<ApiMessageDataResponse<User>>;
    /**
     * Changes a user's password.
     *
     * HTTP: `PATCH /api/v1/users/{id}/password`
     * Access: `admin` only
     */
    changePassword(id: number, payload: ChangePasswordRequest): Promise<ApiMessageResponse>;
    /**
     * Soft-deletes a user.
     *
     * HTTP: `DELETE /api/v1/users/{id}`
     * Access: `admin` only
     */
    delete(id: number): Promise<ApiMessageResponse>;
}
