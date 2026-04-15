export interface DashboardAggregate {
  role: string;
  aggregates: {
    stock_summary?: unknown;
    dry_stock_status?: unknown;
    spending_trend?: unknown;
    current_menu_cycle?: unknown;
    latest_spk_history?: unknown;
    patient_fluctuation?: unknown;
    [key: string]: unknown;
  };
}

export interface DashboardResponse {
  data: DashboardAggregate;
}
