export interface LookupResource {
    id: number;
    name: string;
    created_at?: string | null;
    updated_at?: string | null;
}
export interface LookupListQuery {
    paginate?: boolean;
    page?: number;
    perPage?: number;
    q?: string;
    search?: string;
    sortBy?: "id" | "name" | "created_at" | "updated_at";
    sortDir?: "ASC" | "DESC";
    created_at_from?: string;
    created_at_to?: string;
    updated_at_from?: string;
    updated_at_to?: string;
}
export interface LookupNameRequest {
    name: string;
}
export type RoleListQuery = LookupListQuery;
export type ItemCategory = LookupResource;
export type ItemUnit = LookupResource;
export type TransactionType = LookupResource;
export type ApprovalStatus = LookupResource;
export type MealTime = LookupResource;
