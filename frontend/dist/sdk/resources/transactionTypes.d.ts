import type { ApiClient } from "../client";
import type { ApiListResponse, LookupListQuery, TransactionType } from "../types";
export declare class TransactionTypesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: LookupListQuery): Promise<ApiListResponse<TransactionType>>;
}
