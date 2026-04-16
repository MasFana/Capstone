import type { ApiClient } from "../client";
import type {
  GenerateSpkBasahRequest,
  GenerateSpkKeringPengemasRequest,
  OperationalStockPreviewRequest,
  OperationalStockPreviewResponse,
  SpkBasahDetailResponse,
  SpkBasahGenerateResponse,
  SpkBasahHistoryListResponse,
  SpkMenuCalendarQuery,
  SpkMenuCalendarResponse,
  SpkOverrideRequest,
  SpkOverrideResponse,
  SpkKeringPengemasDetailResponse,
  SpkKeringPengemasGenerateResponse,
  SpkKeringPengemasHistoryListResponse,
  SpkPostStockResponse,
  SpkStockInPrefillResponse
} from "../types";

/**
 * SPK generation/history endpoints.
 *
 * This resource keeps basah and kering/pengemas contracts explicitly separate
 * because they use different generation payloads and detail semantics.
 */
export class SpkResource {
  public constructor(private readonly client: ApiClient) {}

  public basahMenuCalendar(query?: SpkMenuCalendarQuery): Promise<SpkMenuCalendarResponse> {
    return this.client.request<SpkMenuCalendarResponse>({
      method: "GET",
      path: "/spk/basah/menu-calendar",
      ...(query ? { query: buildMenuCalendarQuery(query) } : {})
    });
  }

  public operationalStockPreview(
    payload: OperationalStockPreviewRequest
  ): Promise<OperationalStockPreviewResponse> {
    return this.client.request<OperationalStockPreviewResponse>({
      method: "POST",
      path: "/spk/basah/operational-stock-preview",
      body: payload
    });
  }

  /**
   * Generates a basah SPK for one requested service date, with backend logic
   * potentially expanding to a same-month combined window (day + next day).
   *
   * HTTP: `POST /api/v1/spk/basah/generate`
   * Access: `admin`, `dapur`
   */
  public generateBasah(payload: GenerateSpkBasahRequest): Promise<SpkBasahGenerateResponse> {
    return this.client.request<SpkBasahGenerateResponse>({
      method: "POST",
      path: "/spk/basah/generate",
      body: payload
    });
  }

  /**
   * Lists basah SPK history entries.
   *
   * Envelope semantics: `{ data: [...], meta: { total } }`
   * (intentionally no pagination `links` contract).
   *
   * HTTP: `GET /api/v1/spk/basah/history`
   * Access: `admin`, `gudang`
   */
  public listBasah(): Promise<SpkBasahHistoryListResponse> {
    return this.client.request<SpkBasahHistoryListResponse>({
      method: "GET",
      path: "/spk/basah/history"
    });
  }

  /**
   * Returns one basah SPK history detail.
   *
   * Basah detail/print payload includes combined-window `target_dates` and
   * item rows with non-null day-level `target_date` fields.
   *
   * HTTP: `GET /api/v1/spk/basah/history/{id}`
   * Access: `admin`, `gudang`
   */
  public getBasah(id: number): Promise<SpkBasahDetailResponse> {
    return this.client.request<SpkBasahDetailResponse>({
      method: "GET",
      path: `/spk/basah/history/${id}`
    });
  }

  public overrideBasah(id: number, payload: SpkOverrideRequest): Promise<SpkOverrideResponse> {
    return this.client.request<SpkOverrideResponse>({
      method: "POST",
      path: `/spk/basah/history/${id}/override`,
      body: payload
    });
  }

  public postBasahStock(id: number): Promise<SpkPostStockResponse> {
    return this.client.request<SpkPostStockResponse>({
      method: "POST",
      path: `/spk/basah/history/${id}/post-stock`
    });
  }

  public keringPengemasMenuCalendar(query?: SpkMenuCalendarQuery): Promise<SpkMenuCalendarResponse> {
    return this.client.request<SpkMenuCalendarResponse>({
      method: "GET",
      path: "/spk/kering-pengemas/menu-calendar",
      ...(query ? { query: buildMenuCalendarQuery(query) } : {})
    });
  }

  /**
   * Generates a monthly SPK for kering/pengemas categories.
   *
   * HTTP: `POST /api/v1/spk/kering-pengemas/generate`
   * Access: `admin`, `dapur`
   */
  public generateKeringPengemas(
    payload: GenerateSpkKeringPengemasRequest
  ): Promise<SpkKeringPengemasGenerateResponse> {
    return this.client.request<SpkKeringPengemasGenerateResponse>({
      method: "POST",
      path: "/spk/kering-pengemas/generate",
      body: payload
    });
  }

  /**
   * Lists kering/pengemas SPK history entries.
   *
   * Envelope semantics: `{ data: [...], meta: { total } }`
   * (intentionally no pagination `links` contract).
   *
   * HTTP: `GET /api/v1/spk/kering-pengemas/history`
   * Access: `admin`, `gudang`
   */
  public listKeringPengemas(): Promise<SpkKeringPengemasHistoryListResponse> {
    return this.client.request<SpkKeringPengemasHistoryListResponse>({
      method: "GET",
      path: "/spk/kering-pengemas/history"
    });
  }

  /**
   * Returns one kering/pengemas SPK history detail.
   *
   * Kering/pengemas detail/print payload uses monthly semantics where item rows
   * keep `target_date = null` and print payload includes `target_month`.
   *
   * HTTP: `GET /api/v1/spk/kering-pengemas/history/{id}`
   * Access: `admin`, `gudang`
   */
  public getKeringPengemas(id: number): Promise<SpkKeringPengemasDetailResponse> {
    return this.client.request<SpkKeringPengemasDetailResponse>({
      method: "GET",
      path: `/spk/kering-pengemas/history/${id}`
    });
  }

  public overrideKeringPengemas(id: number, payload: SpkOverrideRequest): Promise<SpkOverrideResponse> {
    return this.client.request<SpkOverrideResponse>({
      method: "POST",
      path: `/spk/kering-pengemas/history/${id}/override`,
      body: payload
    });
  }

  public postKeringPengemasStock(id: number): Promise<SpkPostStockResponse> {
    return this.client.request<SpkPostStockResponse>({
      method: "POST",
      path: `/spk/kering-pengemas/history/${id}/post-stock`
    });
  }

  public stockInPrefill(id: number): Promise<SpkStockInPrefillResponse> {
    return this.client.request<SpkStockInPrefillResponse>({
      method: "GET",
      path: `/spk/stock-in-prefill/${id}`
    });
  }
}

function buildMenuCalendarQuery(query: SpkMenuCalendarQuery): Record<string, string> {
  const result: Record<string, string> = {};

  if (query.month !== undefined) result.month = query.month;
  if (query.date !== undefined) result.date = query.date;
  if (query.start_date !== undefined) result.start_date = query.start_date;
  if (query.end_date !== undefined) result.end_date = query.end_date;

  return result;
}
