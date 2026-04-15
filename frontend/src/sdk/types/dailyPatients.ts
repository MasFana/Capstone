import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse } from "./common";

export interface DailyPatient {
  id: number;
  service_date: string;
  total_patients: number;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface CreateDailyPatientRequest {
  service_date: string;
  total_patients: number;
  notes?: string;
}

export type DailyPatientsListResponse = ApiListResponse<DailyPatient>;
export type DailyPatientResponse = ApiDataResponse<DailyPatient>;
export type DailyPatientCreateResponse = ApiMessageDataResponse<DailyPatient>;
