export interface PaginationMeta {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
  paginated?: boolean;
}

export interface PaginationLinks {
  self: string;
  first: string;
  last: string;
  next: string | null;
  previous: string | null;
}

export interface ApiDataResponse<T> {
  data: T;
}

export interface ApiListResponse<T> {
  data: T[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface ApiMessageResponse {
  message: string;
}

export interface ApiMessageDataResponse<T> extends ApiMessageResponse {
  data: T;
}

export type ApiErrorDetails = Record<string, string> | string[];

export interface ApiValidationErrorResponse extends ApiMessageResponse {
  errors: Record<string, string>;
}

export interface ApiFailureResponse extends ApiMessageResponse {
  errors?: ApiErrorDetails;
}

export type ApiErrorResponse = ApiFailureResponse | ApiValidationErrorResponse;

export type QueryValue = string | number | boolean | null | undefined;

export type QueryParams = Record<string, QueryValue>;

type Without<T, K extends PropertyKey> = {
  [P in Exclude<keyof T, K>]?: never;
};

export type XOR<T, U> = (T & Without<U, keyof T>) | (U & Without<T, keyof U>);
