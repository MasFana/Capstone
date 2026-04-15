import type { ApiClient } from "../client";
import type { ReportResponse, ReportParams } from "../types/reports";
export declare class ReportsResource {
    private readonly client;
    constructor(client: ApiClient);
    getStocks(params: ReportParams): Promise<ReportResponse>;
    getTransactions(params: ReportParams): Promise<ReportResponse>;
    getSpkHistory(params: ReportParams): Promise<ReportResponse>;
    getEvaluation(params: ReportParams): Promise<ReportResponse>;
}
