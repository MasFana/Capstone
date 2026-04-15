import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiMessageDataResponse, ApiMessageResponse, CreateDishCompositionRequest, DishComposition, DishCompositionsListResponse, ListDishCompositionsQuery, UpdateDishCompositionRequest } from "../types";
export declare class DishCompositionsResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: ListDishCompositionsQuery): Promise<DishCompositionsListResponse>;
    get(id: number): Promise<ApiDataResponse<DishComposition>>;
    create(payload: CreateDishCompositionRequest): Promise<ApiMessageDataResponse<DishComposition>>;
    update(id: number, payload: UpdateDishCompositionRequest): Promise<ApiMessageDataResponse<DishComposition>>;
    delete(id: number): Promise<ApiMessageResponse>;
}
