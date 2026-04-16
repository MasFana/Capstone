import type { ApiClient } from "../client";
import type { ApiMessageResponse, CreateDishRequest, Dish, DishCreateResponse, DishesListResponse, ListDishesQuery, UpdateDishRequest } from "../types";
export declare class DishesResource {
    private readonly client;
    constructor(client: ApiClient);
    list(query?: ListDishesQuery): Promise<DishesListResponse>;
    get(id: number): Promise<{
        data: Dish;
    }>;
    create(payload: CreateDishRequest): Promise<DishCreateResponse>;
    update(id: number, payload: UpdateDishRequest): Promise<DishCreateResponse>;
    delete(id: number): Promise<ApiMessageResponse>;
}
