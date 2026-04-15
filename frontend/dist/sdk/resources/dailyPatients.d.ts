import type { ApiClient } from "../client";
import type { CreateDailyPatientRequest, DailyPatientCreateResponse, DailyPatientResponse, DailyPatientsListResponse } from "../types";
/**
 * Daily patient input endpoints.
 */
export declare class DailyPatientsResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Lists daily patient records.
     *
     * HTTP: `GET /api/v1/daily-patients`
     * Access: `admin`, `gudang`
     */
    list(): Promise<DailyPatientsListResponse>;
    /**
     * Returns a single daily patient record by identifier.
     *
     * HTTP: `GET /api/v1/daily-patients/{id}`
     * Access: `admin`, `gudang`
     */
    get(id: number): Promise<DailyPatientResponse>;
    /**
     * Creates a daily patient record.
     *
     * HTTP: `POST /api/v1/daily-patients`
     * Access: `admin`, `dapur`
     */
    create(payload: CreateDailyPatientRequest): Promise<DailyPatientCreateResponse>;
}
