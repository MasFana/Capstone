import type { ApiClient } from "../client";
import type {
  GenerateSpkBasahRequest,
  GenerateSpkKeringPengemasRequest,
  SpkBasahDetailResponse,
  SpkBasahGenerateResponse,
  SpkBasahHistoryListResponse,
  SpkKeringPengemasDetailResponse,
  SpkKeringPengemasGenerateResponse,
  SpkKeringPengemasHistoryListResponse
} from "../types";

/**
 * SPK generation/history endpoints.
 *
 * This resource keeps basah and kering/pengemas contracts explicitly separate
 * because they use different generation payloads and detail semantics.
 */
export class SpkResource {
  public constructor(private readonly client: ApiClient) {}

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
}
