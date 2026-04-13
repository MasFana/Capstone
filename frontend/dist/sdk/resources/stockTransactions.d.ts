import type { ApiClient } from "../client";
import type { ApiDataResponse, ApiListResponse, ApiMessageDataResponse, CreateStockTransactionRequest, DirectStockCorrectionRequest, ListStockTransactionsQuery, StockTransaction, StockTransactionCreateResult, StockTransactionDetail, StockTransactionModerationResult, StockTransactionRevisionResult, SubmitRevisionRequest } from "../types";
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
     * Applies a direct stock correction for a single item.
     *
     * The system derives the mutation type (IN/OUT) and applies the correction
     * to the item's stock level.
     *
     * HTTP: `POST /api/v1/stock-transactions/direct-corrections`
     * Access: `admin` only
     */
    directCorrection(payload: DirectStockCorrectionRequest): Promise<ApiMessageDataResponse<StockTransactionCreateResult>>;
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
     * The backend applies the approved revision as a correction against the
     * parent transaction's stock effect, not as an additional standalone stock
     * movement.
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
