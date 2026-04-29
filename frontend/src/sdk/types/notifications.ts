export interface Notification {
  id: number;
  user_id: number;
  title: string;
  message: string;
  type: string;
  related_id?: number | null;
  is_read: boolean;
  created_at: string;
  updated_at: string;
}

export interface ListNotificationsQuery {
  page?: number;
  perPage?: number;
  is_read?: boolean | number;
  type?: string;
  q?: string;
  /**
   * Allowed sort keys: 'id', 'created_at', 'updated_at', 'is_read', 'type'
   * Default sorting is 'created_at' DESC if not provided.
   */
  sortBy?: "id" | "created_at" | "updated_at" | "is_read" | "type";
  sortDir?: "ASC" | "DESC";
  /**
   * When false, the endpoint returns all matched records without pagination.
   * Defaults to true (paginated).
   */
  paginate?: boolean;
}
