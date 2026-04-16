import type { ApiClient } from "../client";
import type { ApiListResponse, LookupListQuery, MealTime } from "../types";
export declare class MealTimesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: LookupListQuery): Promise<ApiListResponse<MealTime>>;
}
