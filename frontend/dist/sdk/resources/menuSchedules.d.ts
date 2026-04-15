import type { ApiClient } from "../client";
import type { ApiDataResponse, CreateMenuScheduleRequest, MenuCalendarQuery, MenuCalendarResponse, MenuSchedule, MenuScheduleCreateResponse, MenuSchedulesListResponse, UpdateMenuScheduleRequest } from "../types";
export declare class MenuSchedulesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(): Promise<MenuSchedulesListResponse>;
    get(id: number): Promise<ApiDataResponse<MenuSchedule>>;
    create(payload: CreateMenuScheduleRequest): Promise<MenuScheduleCreateResponse>;
    update(id: number, payload: UpdateMenuScheduleRequest): Promise<MenuScheduleCreateResponse>;
    calendarProjection(query?: MenuCalendarQuery): Promise<MenuCalendarResponse>;
}
