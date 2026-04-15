import type { ApiClient } from "../client";
import type { CreateStockOpnameRequest, RejectStockOpnameRequest, StockOpnameResponse, StockOpnameActionResponse } from "../types/stockOpnames";
export declare class StockOpnamesResource {
    private readonly client;
    constructor(client: ApiClient);
    create(request: CreateStockOpnameRequest): Promise<StockOpnameActionResponse>;
    get(id: number): Promise<StockOpnameResponse>;
    submit(id: number): Promise<StockOpnameActionResponse>;
    approve(id: number): Promise<StockOpnameActionResponse>;
    reject(id: number, request: RejectStockOpnameRequest): Promise<StockOpnameActionResponse>;
    post(id: number): Promise<StockOpnameActionResponse>;
}
