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
  item_unit_base_id: number | null;
  item_unit_convert_id: number | null;
  conversion_base: number;
  qty: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  category: ItemCategorySummary;
  item_unit_base?: { id: number | null; name: string | null } | null;
  item_unit_convert?: { id: number | null; name: string | null } | null;
}

type ItemCategoryIdentifier = XOR<{ item_category_id: number }, { item_category_name: string }>;
type OptionalItemCategoryIdentifier = ItemCategoryIdentifier | { item_category_id?: undefined; item_category_name?: undefined };

export interface ListItemsQuery {
  page?: number;
  perPage?: number;
  item_category_id?: number;
  is_active?: boolean;
  q?: string;
  search?: string;
  sortBy?: "id" | "name" | "item_category_id" | "created_at" | "updated_at";
  sortDir?: "ASC" | "DESC";
  created_at_from?: string;
  created_at_to?: string;
  updated_at_from?: string;
  updated_at_to?: string;
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
