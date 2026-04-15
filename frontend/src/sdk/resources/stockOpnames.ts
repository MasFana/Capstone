import type { ApiClient } from "../client";
import type { 
  CreateStockOpnameRequest, 
  RejectStockOpnameRequest, 
  StockOpnameResponse, 
  StockOpnameActionResponse 
} from "../types/stockOpnames";

export class StockOpnamesResource {
  private readonly client: ApiClient;

  public constructor(client: ApiClient) {
    this.client = client;
  }

  public async create(request: CreateStockOpnameRequest): Promise<StockOpnameActionResponse> {
    return this.client.request<StockOpnameActionResponse>({
      method: "POST",
      path: "/stock-opnames",
      body: request
    });
  }

  public async get(id: number): Promise<StockOpnameResponse> {
    return this.client.request<StockOpnameResponse>({
      method: "GET",
      path: `/stock-opnames/${id}`
    });
  }

  public async submit(id: number): Promise<StockOpnameActionResponse> {
    return this.client.request<StockOpnameActionResponse>({
      method: "POST",
      path: `/stock-opnames/${id}/submit`
    });
  }

  public async approve(id: number): Promise<StockOpnameActionResponse> {
    return this.client.request<StockOpnameActionResponse>({
      method: "POST",
      path: `/stock-opnames/${id}/approve`
    });
  }

  public async reject(id: number, request: RejectStockOpnameRequest): Promise<StockOpnameActionResponse> {
    return this.client.request<StockOpnameActionResponse>({
      method: "POST",
      path: `/stock-opnames/${id}/reject`,
      body: request
    });
  }

  public async post(id: number): Promise<StockOpnameActionResponse> {
    return this.client.request<StockOpnameActionResponse>({
      method: "POST",
      path: `/stock-opnames/${id}/post`
    });
  }
}
