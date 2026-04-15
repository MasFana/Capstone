import type { ApiClient } from "../client";
import type {
  CreateDailyPatientRequest,
  DailyPatientCreateResponse,
  DailyPatientResponse,
  DailyPatientsListResponse
} from "../types";

/**
 * Daily patient input endpoints.
 */
export class DailyPatientsResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Lists daily patient records.
   *
   * HTTP: `GET /api/v1/daily-patients`
   * Access: `admin`, `gudang`
   */
  public list(): Promise<DailyPatientsListResponse> {
    return this.client.request<DailyPatientsListResponse>({
      method: "GET",
      path: "/daily-patients"
    });
  }

  /**
   * Returns a single daily patient record by identifier.
   *
   * HTTP: `GET /api/v1/daily-patients/{id}`
   * Access: `admin`, `gudang`
   */
  public get(id: number): Promise<DailyPatientResponse> {
    return this.client.request<DailyPatientResponse>({
      method: "GET",
      path: `/daily-patients/${id}`
    });
  }

  /**
   * Creates a daily patient record.
   *
   * HTTP: `POST /api/v1/daily-patients`
   * Access: `admin`, `dapur`
   */
  public create(payload: CreateDailyPatientRequest): Promise<DailyPatientCreateResponse> {
    return this.client.request<DailyPatientCreateResponse>({
      method: "POST",
      path: "/daily-patients",
      body: payload
    });
  }
}
