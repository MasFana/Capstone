import type { ApiClient } from "../client";
import type { DashboardResponse } from "../types/dashboard";

export class DashboardResource {
  private readonly client: ApiClient;

  public constructor(client: ApiClient) {
    this.client = client;
  }

  public async getAggregate(): Promise<DashboardResponse> {
    return this.client.request<DashboardResponse>({
      method: "GET",
      path: "/dashboard"
    });
  }
}
