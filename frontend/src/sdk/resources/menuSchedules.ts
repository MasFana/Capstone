import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  CreateMenuScheduleRequest,
  MenuCalendarQuery,
  MenuCalendarResponse,
  MenuSchedule,
  MenuScheduleCreateResponse,
  MenuSchedulesListResponse,
  UpdateMenuScheduleRequest
} from "../types";

export class MenuSchedulesResource {
  public constructor(private readonly client: ApiClient) {}

  public list(): Promise<MenuSchedulesListResponse> {
    return this.client.request<MenuSchedulesListResponse>({
      method: "GET",
      path: "/menu-schedules"
    });
  }

  public get(id: number): Promise<ApiDataResponse<MenuSchedule>> {
    return this.client.request<ApiDataResponse<MenuSchedule>>({
      method: "GET",
      path: `/menu-schedules/${id}`
    });
  }

  public create(payload: CreateMenuScheduleRequest): Promise<MenuScheduleCreateResponse> {
    return this.client.request<MenuScheduleCreateResponse>({
      method: "POST",
      path: "/menu-schedules",
      body: payload
    });
  }

  public update(id: number, payload: UpdateMenuScheduleRequest): Promise<MenuScheduleCreateResponse> {
    return this.client.request<MenuScheduleCreateResponse>({
      method: "PUT",
      path: `/menu-schedules/${id}`,
      body: payload
    });
  }

  public calendarProjection(query?: MenuCalendarQuery): Promise<MenuCalendarResponse> {
    return this.client.request<MenuCalendarResponse>({
      method: "GET",
      path: "/menu-calendar",
      ...(query ? { query: buildMenuCalendarQuery(query) } : {})
    });
  }
}

function buildMenuCalendarQuery(query: MenuCalendarQuery): Record<string, string> {
  const result: Record<string, string> = {};

  if (query.month !== undefined) result.month = query.month;
  if (query.date !== undefined) result.date = query.date;
  if (query.start_date !== undefined) result.start_date = query.start_date;
  if (query.end_date !== undefined) result.end_date = query.end_date;

  return result;
}
