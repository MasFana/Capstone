import type {
  ApiDataResponse,
  ApiMessageResponse,
  LoginRequest,
  LoginResponse,
  SelfServiceChangePasswordRequest,
  User
} from "../types";
import type { ApiClient } from "../client";

/**
 * Auth endpoints for login and bearer-backed session inspection.
 */
export class AuthResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Logs a user in.
   *
   * HTTP: `POST /api/v1/auth/login`
   * Access: public
   */
  public login(payload: LoginRequest): Promise<LoginResponse> {
    return this.client.request<LoginResponse>({
      method: "POST",
      path: "/auth/login",
      body: payload
    });
  }

  /**
   * Returns the current authenticated user.
   *
   * HTTP: `GET /api/v1/auth/me`
   * Access: authenticated `admin`, `dapur`, `gudang`
   */
  public me(): Promise<ApiDataResponse<User>> {
    return this.client.request<ApiDataResponse<User>>({
      method: "GET",
      path: "/auth/me"
    });
  }

  /**
   * Revokes the current access token.
   *
   * HTTP: `POST /api/v1/auth/logout`
   * Access: authenticated `admin`, `dapur`, `gudang`
   */
  public logout(): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "POST",
      path: "/auth/logout"
    });
  }

  /**
   * Changes the current authenticated user's password.
   *
   * HTTP: `PATCH /api/v1/auth/password`
   * Access: authenticated `admin`, `dapur`, `gudang`
   */
  public changePassword(payload: SelfServiceChangePasswordRequest): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "PATCH",
      path: "/auth/password",
      body: payload
    });
  }
}
