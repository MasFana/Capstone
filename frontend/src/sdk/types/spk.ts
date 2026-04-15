import type { ApiDataResponse, ApiMessageDataResponse } from "./common";

export interface SpkActorSummary {
  id: number | null;
  name: string | null;
  username: string | null;
}

export interface SpkCategorySummary {
  id: number | null;
  name: string | null;
}

export interface SpkHistoryEntry {
  id: number;
  version: number;
  scope_key: string;
  is_latest: boolean;
  calculation_scope: string;
  calculation_date: string;
  target_date_start: string | null;
  target_date_end: string | null;
  target_month: string | null;
  estimated_patients: number;
  is_finish: boolean;
  created_at: string;
  user: SpkActorSummary;
  category: SpkCategorySummary;
}

export interface SpkItemOverride {
  is_overridden: boolean;
  reason: string | null;
  overridden_by: number | null;
  overridden_at: string | null;
}

export interface SpkBaseRecommendationItem {
  id: number;
  item_id: number;
  item_name: string | null;
  item_unit_base: string | null;
  item_unit_convert: string | null;
  current_stock_qty: number;
  required_qty: number;
  system_recommended_qty: number;
  final_recommended_qty: number;
  override: SpkItemOverride;
}

/**
 * Basah detail rows are day-scoped because generation uses a same-month
 * combined window (`service_date` + optional next-day).
 */
export interface SpkBasahRecommendationItem extends SpkBaseRecommendationItem {
  target_date: string;
}

/**
 * Kering/Pengemas detail rows are month-scoped, so `target_date` remains null.
 */
export interface SpkKeringPengemasRecommendationItem extends SpkBaseRecommendationItem {
  target_date: null;
}

export interface SpkBasahPrintReady {
  spk_id: number;
  spk_type: string;
  version: number;
  calculation_date: string;
  target_date_start: string | null;
  target_date_end: string | null;
  target_dates: string[];
  estimated_patients: number;
  category_name: string | null;
  generated_by: string | null;
  recommendations: SpkBasahRecommendationItem[];
}

export interface SpkKeringPengemasPrintReady {
  spk_id: number;
  spk_type: string;
  version: number;
  calculation_date: string;
  target_date_start: string | null;
  target_date_end: string | null;
  target_month: string | null;
  estimated_patients: number;
  category_name: string | null;
  generated_by: string | null;
  recommendations: SpkKeringPengemasRecommendationItem[];
}

export interface SpkBasahDetail {
  id: number;
  version: number;
  scope_key: string;
  is_latest: boolean;
  spk_type: string;
  calculation_scope: string;
  calculation_date: string;
  target_date_start: string | null;
  target_date_end: string | null;
  target_month: string | null;
  estimated_patients: number;
  is_finish: boolean;
  created_at: string;
  updated_at: string;
  user: {
    id: number;
    name: string | null;
    username: string | null;
  };
  category: {
    id: number;
    name: string | null;
  };
  items: SpkBasahRecommendationItem[];
  print_ready: SpkBasahPrintReady;
}

export interface SpkKeringPengemasDetail {
  id: number;
  version: number;
  scope_key: string;
  is_latest: boolean;
  spk_type: string;
  calculation_scope: string;
  calculation_date: string;
  target_date_start: string | null;
  target_date_end: string | null;
  target_month: string | null;
  estimated_patients: number;
  is_finish: boolean;
  created_at: string;
  updated_at: string;
  user: {
    id: number;
    name: string | null;
    username: string | null;
  };
  category: {
    id: number;
    name: string | null;
  };
  items: SpkKeringPengemasRecommendationItem[];
  print_ready: SpkKeringPengemasPrintReady;
}

export interface GenerateSpkBasahRequest {
  service_date: string;
}

export interface GenerateSpkKeringPengemasRequest {
  target_month: string;
}

export interface SpkBasahGenerateResult {
  id: number;
  version: number;
  scope_key: string;
  target_dates: string[];
  estimated_patients: number;
}

export interface SpkKeringPengemasGenerateResult {
  id: number;
  version: number;
  scope_key: string;
  target_month: string;
}

export type SpkBasahGenerateResponse = ApiMessageDataResponse<SpkBasahGenerateResult>;
export type SpkKeringPengemasGenerateResponse = ApiMessageDataResponse<SpkKeringPengemasGenerateResult>;

export interface SpkHistoryListMeta {
  total: number;
}

export interface SpkHistoryListResponse<T> {
  data: T[];
  meta: SpkHistoryListMeta;
}

export type SpkBasahHistoryListResponse = SpkHistoryListResponse<SpkHistoryEntry>;
export type SpkKeringPengemasHistoryListResponse = SpkHistoryListResponse<SpkHistoryEntry>;

export type SpkBasahDetailResponse = ApiDataResponse<SpkBasahDetail>;
export type SpkKeringPengemasDetailResponse = ApiDataResponse<SpkKeringPengemasDetail>;
