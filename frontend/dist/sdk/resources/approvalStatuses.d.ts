import type { ApiClient } from "../client";
import type { ApiListResponse, ApprovalStatus, LookupListQuery } from "../types";
export declare class ApprovalStatusesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: LookupListQuery): Promise<ApiListResponse<ApprovalStatus>>;
}
