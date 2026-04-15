import type { ApiClient } from "../client";
import type { DashboardResponse } from "../types/dashboard";
export declare class DashboardResource {
    private readonly client;
    constructor(client: ApiClient);
    getAggregate(): Promise<DashboardResponse>;
}
