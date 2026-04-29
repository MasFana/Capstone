import type { ApiClient } from "../client";
import type { ApiListResponse, ApiMessageDataResponse, ApiMessageResponse, Notification, ListNotificationsQuery } from "../types";
export declare class NotificationsResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: ListNotificationsQuery): Promise<ApiListResponse<Notification>>;
    markAsRead(id: number): Promise<ApiMessageDataResponse<Notification>>;
    markAllAsRead(): Promise<ApiMessageResponse>;
    /**
     * Delete a single notification owned by the current user.
     *
     * HTTP: DELETE /api/v1/notifications/{id}
     */
    delete(id: number): Promise<ApiMessageResponse>;
    /**
     * Delete all notifications for the current user.
     *
     * HTTP: DELETE /api/v1/notifications
     */
    deleteAll(): Promise<ApiMessageResponse>;
}
