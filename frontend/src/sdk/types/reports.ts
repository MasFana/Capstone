export interface ReportSummary {
  total_items?: number;
  active_items?: number;
  total_qty?: number;
  total_rows?: number;
  total_spk?: number;
  planned_total_qty?: number;
  realization_total_qty?: number;
  variance_total_qty?: number;
  [key: string]: unknown;
}

export interface ReportRow {
  [key: string]: unknown;
}

export interface CompatibilityProjection {
  contract: {
    spk_calculations: string[];
    spk_recommendations: string[];
    [key: string]: string[];
  };
  rows: unknown[];
}

export interface ReportData {
  report_type: string;
  summary: ReportSummary;
  rows: ReportRow[];
  compatibility_projection?: CompatibilityProjection;
}

export interface ReportResponse {
  data: ReportData;
}

export interface ReportParams {
  period_start: string;
  period_end: string;
}
