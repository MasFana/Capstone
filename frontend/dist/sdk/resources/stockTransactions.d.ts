import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse, CreateStockTransactionRequest, ListStockTransactionsQuery, StockTransaction, StockTransactionCreateResult, StockTransactionDetail, StockTransactionModerationResult, StockTransactionRevisionResult, SubmitRevisionRequest } from "../types";
/**
 * Stock transaction and revision workflow endpoints.
 */
export declare class StockTransactionsResource {
    private readonly client;
    constructor(client: ApiClient);
    /**
     * Lists stock transactions with pagination.
     *
     * HTTP: `GET /api/v1/stock-transactions`
     * Access: `admin`, `gudang`
     */
    list(query?: ListStockTransactionsQuery): Promise<ApiListResponse<StockTransaction>>;
    /**
     * Returns a stock transaction header.
     *
     * HTTP: `GET /api/v1/stock-transactions/{id}`
     * Access: `admin`, `gudang`
     */
    get(id: number): Promise<ApiDataResponse<StockTransaction>>;
    /**
     * Returns the detail rows for a stock transaction.
     *
     * HTTP: `GET /api/v1/stock-transactions/{id}/details`
     * Access: `admin`, `gudang`
     */
    details(id: number): Promise<ApiDataResponse<StockTransactionDetail[]>>;
    /**
     * Creates a normal stock transaction.
     *
     * HTTP: `POST /api/v1/stock-transactions`
     * Access: `admin`, `gudang`
     */
    create(payload: CreateStockTransactionRequest): Promise<ApiMessageDataResponse<StockTransactionCreateResult>>;
    /**
     * Submits a revision for an existing transaction.
     *
     * HTTP: `POST /api/v1/stock-transactions/{id}/submit-revision`
     * Access: `admin`, `gudang`
     */
    submitRevision(id: number, payload: SubmitRevisionRequest): Promise<ApiMessageDataResponse<StockTransactionRevisionResult>>;
    /**
     * Approves a revision transaction.
     *
     * HTTP: `POST /api/v1/stock-transactions/{id}/approve`
     * Access: `admin` only
     */
    approve(id: number): Promise<ApiMessageDataResponse<StockTransactionModerationResult>>;
    /**
     * Rejects a revision transaction.
     *
     * HTTP: `POST /api/v1/stock-transactions/{id}/reject`
     * Access: `admin` only
     */
    reject(id: number): Promise<ApiMessageDataResponse<StockTransactionModerationResult>>;
}
