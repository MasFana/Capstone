import type { ApiClient } from "../client";
import type { ReportResponse, ReportParams } from "../types/reports";

export class ReportsResource {
  private readonly client: ApiClient;

  public constructor(client: ApiClient) {
    this.client = client;
  }

  public async getStocks(params: ReportParams): Promise<ReportResponse> {
    return this.client.request<ReportResponse>({
      method: "GET",
      path: "/reports/stocks",
      query: { ...params }
    });
  }

  public async getTransactions(params: ReportParams): Promise<ReportResponse> {
    return this.client.request<ReportResponse>({
      method: "GET",
      path: "/reports/transactions",
      query: { ...params }
    });
  }

  public async getSpkHistory(params: ReportParams): Promise<ReportResponse> {
    return this.client.request<ReportResponse>({
      method: "GET",
      path: "/reports/spk-history",
      query: { ...params }
    });
  }

  public async getEvaluation(params: ReportParams): Promise<ReportResponse> {
    return this.client.request<ReportResponse>({
      method: "GET",
      path: "/reports/evaluation",
      query: { ...params }
    });
  }
}
