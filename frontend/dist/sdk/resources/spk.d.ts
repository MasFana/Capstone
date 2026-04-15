import type { ApiClient } from "../client";
import type { GenerateSpkBasahRequest, GenerateSpkKeringPengemasRequest, SpkBasahDetailResponse, SpkBasahGenerateResponse, SpkBasahHistoryListResponse, SpkKeringPengemasDetailResponse, SpkKeringPengemasGenerateResponse, SpkKeringPengemasHistoryListResponse } from "../types";
/**
 * SPK generation/history endpoints.
 *
 * This resource keeps basah and kering/pengemas contracts explicitly separate
 * because they use different generation payloads and detail semantics.
 */
export declare class SpkResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Generates a basah SPK for one requested service date, with backend logic
     * potentially expanding to a same-month combined window (day + next day).
     *
     * HTTP: `POST /api/v1/spk/basah/generate`
     * Access: `admin`, `dapur`
     */
    generateBasah(payload: GenerateSpkBasahRequest): Promise<SpkBasahGenerateResponse>;
    /**
     * Lists basah SPK history entries.
     *
     * Envelope semantics: `{ data: [...], meta: { total } }`
     * (intentionally no pagination `links` contract).
     *
     * HTTP: `GET /api/v1/spk/basah/history`
     * Access: `admin`, `gudang`
     */
    listBasah(): Promise<SpkBasahHistoryListResponse>;
    /**
     * Returns one basah SPK history detail.
     *
     * Basah detail/print payload includes combined-window `target_dates` and
     * item rows with non-null day-level `target_date` fields.
     *
     * HTTP: `GET /api/v1/spk/basah/history/{id}`
     * Access: `admin`, `gudang`
     */
    getBasah(id: number): Promise<SpkBasahDetailResponse>;
    /**
     * Generates a monthly SPK for kering/pengemas categories.
     *
     * HTTP: `POST /api/v1/spk/kering-pengemas/generate`
     * Access: `admin`, `dapur`
     */
    generateKeringPengemas(payload: GenerateSpkKeringPengemasRequest): Promise<SpkKeringPengemasGenerateResponse>;
    /**
     * Lists kering/pengemas SPK history entries.
     *
     * Envelope semantics: `{ data: [...], meta: { total } }`
     * (intentionally no pagination `links` contract).
     *
     * HTTP: `GET /api/v1/spk/kering-pengemas/history`
     * Access: `admin`, `gudang`
     */
    listKeringPengemas(): Promise<SpkKeringPengemasHistoryListResponse>;
    /**
     * Returns one kering/pengemas SPK history detail.
     *
     * Kering/pengemas detail/print payload uses monthly semantics where item rows
     * keep `target_date = null` and print payload includes `target_month`.
     *
     * HTTP: `GET /api/v1/spk/kering-pengemas/history/{id}`
     * Access: `admin`, `gudang`
     */
    getKeringPengemas(id: number): Promise<SpkKeringPengemasDetailResponse>;
}
