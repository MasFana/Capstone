import type { ApiClient } from "../client";
import type {
  ApiListResponse,
  ApiMessageResponse,
  Notification,
  ListNotificationsQuery,
} from "../types";

export class NotificationsResource {
  public constructor(private readonly client: ApiClient) {}

  public list(
    query?: ListNotificationsQuery,
  ): Promise<ApiListResponse<Notification>> {
    return this.client.request<ApiListResponse<Notification>>({
      method: "GET",
      path: "/notifications",
      ...(query ? { query: buildNotificationsQuery(query) } : {}),
    });
  }

  /**
   * Mark a single notification as read for the current user.
   *
   * HTTP: POST /api/v1/notifications/{id}/read
   */
  public markAsRead(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "POST",
      path: `/notifications/${id}/read`,
    });
  }

  /**
   * Mark all notifications as read for the current user.
   *
   * HTTP: POST /api/v1/notifications/read-all
   */
  public markAllAsRead(): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "POST",
      path: "/notifications/read-all",
    });
  }

  /**
   * Delete a single notification owned by the current user.
   *
   * HTTP: DELETE /api/v1/notifications/{id}
   */
  public delete(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/notifications/${id}`,
    });
  }

  /**
   * Delete all notifications for the current user.
   *
   * HTTP: DELETE /api/v1/notifications
   */
  public deleteAll(): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: "/notifications",
    });
  }
}

function buildNotificationsQuery(
  query: ListNotificationsQuery,
): Record<string, string | number | boolean> {
  const result: Record<string, string | number | boolean> = {};

  // Pagination
  if (query.page !== undefined) result.page = query.page;
  if (query.perPage !== undefined) result.perPage = query.perPage;

  // Filters
  if (query.is_read !== undefined) result.is_read = query.is_read;
  if (query.type !== undefined) result.type = query.type;
  if (query.q !== undefined) result.q = query.q;

  // Sorting
  if (query.sortBy !== undefined) result.sortBy = query.sortBy;
  if (query.sortDir !== undefined) result.sortDir = query.sortDir;

  // Paginate toggle (when false, backend returns all matched records)
  if (query.paginate !== undefined) result.paginate = query.paginate;

  return result;
}
