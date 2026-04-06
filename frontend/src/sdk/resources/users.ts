import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiMessageDataResponse,
  ApiMessageResponse,
  ChangePasswordRequest,
  CreateUserRequest,
  UpdateUserRequest,
  User
} from "../types";

/**
 * User management endpoints.
 */
export class UsersResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Lists all users.
   *
   * HTTP: `GET /api/v1/users`
   * Access: `admin` only
   */
  public list(): Promise<ApiDataResponse<User[]>> {
    return this.client.request<ApiDataResponse<User[]>>({
      method: "GET",
      path: "/users"
    });
  }

  /**
   * Returns a single user by identifier.
   *
   * HTTP: `GET /api/v1/users/{id}`
   * Access: `admin` only
   */
  public get(id: number): Promise<ApiDataResponse<User>> {
    return this.client.request<ApiDataResponse<User>>({
      method: "GET",
      path: `/users/${id}`
    });
  }

  /**
   * Creates a new user.
   *
   * HTTP: `POST /api/v1/users`
   * Access: `admin` only
   */
  public create(payload: CreateUserRequest): Promise<ApiMessageDataResponse<User>> {
    return this.client.request<ApiMessageDataResponse<User>>({
      method: "POST",
      path: "/users",
      body: payload
    });
  }

  /**
   * Updates a user profile and role assignment.
   *
   * HTTP: `PUT /api/v1/users/{id}`
   * Access: `admin` only
   */
  public update(id: number, payload: UpdateUserRequest): Promise<ApiMessageDataResponse<User>> {
    return this.client.request<ApiMessageDataResponse<User>>({
      method: "PUT",
      path: `/users/${id}`,
      body: payload
    });
  }

  /**
   * Activates a user account.
   *
   * HTTP: `PATCH /api/v1/users/{id}/activate`
   * Access: `admin` only
   */
  public activate(id: number): Promise<ApiMessageDataResponse<User>> {
    return this.client.request<ApiMessageDataResponse<User>>({
      method: "PATCH",
      path: `/users/${id}/activate`
    });
  }

  /**
   * Deactivates a user account.
   *
   * HTTP: `PATCH /api/v1/users/{id}/deactivate`
   * Access: `admin` only
   */
  public deactivate(id: number): Promise<ApiMessageDataResponse<User>> {
    return this.client.request<ApiMessageDataResponse<User>>({
      method: "PATCH",
      path: `/users/${id}/deactivate`
    });
  }

  /**
   * Changes a user's password.
   *
   * HTTP: `PATCH /api/v1/users/{id}/password`
   * Access: `admin` only
   */
  public changePassword(id: number, payload: ChangePasswordRequest): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "PATCH",
      path: `/users/${id}/password`,
      body: payload
    });
  }

  /**
   * Soft-deletes a user.
   *
   * HTTP: `DELETE /api/v1/users/{id}`
   * Access: `admin` only
   */
  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/users/${id}`
    });
  }
}
