import type { XOR } from "./common";
export interface ItemCategorySummary {
    id: number;
    name: string | null;
}
export interface Item {
    id: number;
    item_category_id: number;
    name: string;
    unit_base: string;
    unit_convert: string;
    conversion_base: number;
    qty: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    category: ItemCategorySummary;
}
type ItemCategoryIdentifier = XOR<{
    item_category_id: number;
}, {
    item_category_name: string;
}>;
type OptionalItemCategoryIdentifier = ItemCategoryIdentifier | {
    item_category_id?: undefined;
    item_category_name?: undefined;
};
export interface ListItemsQuery {
    page?: number;
    perPage?: number;
    item_category_id?: number;
    is_active?: boolean;
    q?: string;
}
export type CreateItemRequest = ItemCategoryIdentifier & {
    name: string;
    unit_base: string;
    unit_convert: string;
    conversion_base: number;
    is_active?: boolean;
};
export type UpdateItemRequest = OptionalItemCategoryIdentifier & {
    name?: string;
    unit_base?: string;
    unit_convert?: string;
    conversion_base?: number;
    is_active?: boolean;
};
export {};
