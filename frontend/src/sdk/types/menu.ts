import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse } from "./common";

export interface Menu {
  id: number;
  name: string;
}

export interface Dish {
  id: number;
  name: string;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface DishSummary {
  id: number;
  name: string | null;
}

export interface DishCompositionItemSummary {
  id: number;
  name: string | null;
  unit_base: string | null;
  is_active: boolean | null;
}

export interface DishComposition {
  id: number;
  dish_id: number;
  item_id: number;
  qty_per_patient: string;
  created_at: string | null;
  updated_at: string | null;
  dish: DishSummary;
  item: DishCompositionItemSummary;
}

export interface MenuSlot {
  id: number;
  menu_id: number;
  meal_time_id: number;
  dish_id: number;
  created_at: string | null;
  updated_at: string | null;
  menu: Menu;
  meal_time: {
    id: number;
    name: string | null;
  };
  dish: DishSummary;
}

export interface MenuSchedule {
  id: number;
  day_of_month: number;
  menu_id: number;
  created_at: string | null;
  updated_at: string | null;
  menu: Menu;
}

export interface MenuCalendarEntry {
  date: string;
  day_of_month: number;
  menu_id: number;
  menu_name: string;
}

export interface MenuCalendarMonthMeta {
  month: string;
  total: number;
}

export interface MenuCalendarRangeMeta {
  start_date: string;
  end_date: string;
  total: number;
}

export interface MenuCalendarDateResponse extends ApiDataResponse<MenuCalendarEntry> {}

export interface MenuCalendarMonthResponse extends ApiDataResponse<MenuCalendarEntry[]> {
  meta: MenuCalendarMonthMeta;
}

export interface MenuCalendarRangeResponse extends ApiDataResponse<MenuCalendarEntry[]> {
  meta: MenuCalendarRangeMeta;
}

export type MenuCalendarResponse = MenuCalendarDateResponse | MenuCalendarMonthResponse | MenuCalendarRangeResponse;

export interface ListDishesQuery {
  page?: number;
  perPage?: number;
  q?: string;
  search?: string;
  sortBy?: "id" | "name" | "created_at" | "updated_at";
  sortDir?: "ASC" | "DESC";
  created_at_from?: string;
  created_at_to?: string;
  updated_at_from?: string;
  updated_at_to?: string;
}

export interface ListDishCompositionsQuery {
  page?: number;
  perPage?: number;
  dish_id?: number;
  item_id?: number;
  q?: string;
  search?: string;
  sortBy?: "id" | "dish_id" | "item_id" | "qty_per_patient" | "created_at" | "updated_at";
  sortDir?: "ASC" | "DESC";
  created_at_from?: string;
  created_at_to?: string;
  updated_at_from?: string;
  updated_at_to?: string;
}

export interface MenuCalendarQuery {
  month?: string;
  date?: string;
  start_date?: string;
  end_date?: string;
}

export interface CreateDishRequest {
  name: string;
}

export interface UpdateDishRequest {
  name?: string;
}

export interface CreateDishCompositionRequest {
  dish_id: number;
  item_id: number;
  qty_per_patient: string;
}

export interface UpdateDishCompositionRequest {
  dish_id?: number;
  item_id?: number;
  qty_per_patient?: string;
}

export interface CreateMenuSlotRequest {
  menu_id: number;
  meal_time_id: number;
  dish_id: number;
}

export interface CreateMenuScheduleRequest {
  day_of_month: number;
  menu_id: number;
}

export interface UpdateMenuScheduleRequest {
  day_of_month?: number;
  menu_id?: number;
}

export type DishesListResponse = ApiListResponse<Dish>;
export type DishCompositionsListResponse = ApiListResponse<DishComposition>;
export type MenusListResponse = ApiListResponse<Menu>;
export type MenuSlotsListResponse = ApiListResponse<MenuSlot>;
export type MenuSchedulesListResponse = ApiListResponse<MenuSchedule>;

export type DishCreateResponse = ApiMessageDataResponse<Dish>;
export type DishCompositionCreateResponse = ApiMessageDataResponse<DishComposition>;
export type MenuSlotCreateResponse = ApiMessageDataResponse<MenuSlot>;
export type MenuScheduleCreateResponse = ApiMessageDataResponse<MenuSchedule>;
