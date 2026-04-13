import type { ApiClient } from "../client";
import type {
  ApiDataResponse,
  ApiListResponse,
  ApiMessageDataResponse,
  CreateStockTransactionRequest,
  DirectStockCorrectionRequest,
  ListStockTransactionsQuery,
  StockTransaction,
  StockTransactionCreateResult,
  StockTransactionDetail,
  StockTransactionModerationResult,
  StockTransactionRevisionResult,
  SubmitRevisionRequest
} from "../types";

/**
 * Stock transaction and revision workflow endpoints.
 */
export class StockTransactionsResource {
  public constructor(private readonly client: ApiClient) {}

  /**
   * Lists stock transactions with pagination.
   *
   * HTTP: `GET /api/v1/stock-transactions`
   * Access: `admin`, `gudang`
   */
  public list(query?: ListStockTransactionsQuery): Promise<ApiListResponse<StockTransaction>> {
    return this.client.request<ApiListResponse<StockTransaction>>({
      method: "GET",
      path: "/stock-transactions",
      ...(query ? { query: buildStockTransactionsQuery(query) } : {})
    });
  }

  /**
   * Returns a stock transaction header.
   *
   * HTTP: `GET /api/v1/stock-transactions/{id}`
   * Access: `admin`, `gudang`
   */
  public get(id: number): Promise<ApiDataResponse<StockTransaction>> {
    return this.client.request<ApiDataResponse<StockTransaction>>({
      method: "GET",
      path: `/stock-transactions/${id}`
    });
  }

  /**
   * Returns the detail rows for a stock transaction.
   *
   * HTTP: `GET /api/v1/stock-transactions/{id}/details`
   * Access: `admin`, `gudang`
   */
  public details(id: number): Promise<ApiDataResponse<StockTransactionDetail[]>> {
    return this.client.request<ApiDataResponse<StockTransactionDetail[]>>({
      method: "GET",
      path: `/stock-transactions/${id}/details`
    });
  }

  /**
   * Creates a normal stock transaction.
   *
   * HTTP: `POST /api/v1/stock-transactions`
   * Access: `admin`, `gudang`
   */
  public create(payload: CreateStockTransactionRequest): Promise<ApiMessageDataResponse<StockTransactionCreateResult>> {
    return this.client.request<ApiMessageDataResponse<StockTransactionCreateResult>>({
      method: "POST",
      path: "/stock-transactions",
      body: payload
    });
  }

  /**
   * Applies a direct stock correction for a single item.
   *
   * The system derives the mutation type (IN/OUT) and applies the correction
   * to the item's stock level.
   *
   * HTTP: `POST /api/v1/stock-transactions/direct-corrections`
   * Access: `admin` only
   */
  public directCorrection(payload: DirectStockCorrectionRequest): Promise<ApiMessageDataResponse<StockTransactionCreateResult>> {
    return this.client.request<ApiMessageDataResponse<StockTransactionCreateResult>>({
      method: "POST",
      path: "/stock-transactions/direct-corrections",
      body: payload
    });
  }

  /**
   * Submits a revision for an existing transaction.
   *
   * HTTP: `POST /api/v1/stock-transactions/{id}/submit-revision`
   * Access: `admin`, `gudang`
   */
  public submitRevision(id: number, payload: SubmitRevisionRequest): Promise<ApiMessageDataResponse<StockTransactionRevisionResult>> {
    return this.client.request<ApiMessageDataResponse<StockTransactionRevisionResult>>({
      method: "POST",
      path: `/stock-transactions/${id}/submit-revision`,
      body: payload
    });
  }

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
  public approve(id: number): Promise<ApiMessageDataResponse<StockTransactionModerationResult>> {
    return this.client.request<ApiMessageDataResponse<StockTransactionModerationResult>>({
      method: "POST",
      path: `/stock-transactions/${id}/approve`
    });
  }

  /**
   * Rejects a revision transaction.
   *
   * HTTP: `POST /api/v1/stock-transactions/{id}/reject`
   * Access: `admin` only
   */
  public reject(id: number): Promise<ApiMessageDataResponse<StockTransactionModerationResult>> {
    return this.client.request<ApiMessageDataResponse<StockTransactionModerationResult>>({
      method: "POST",
      path: `/stock-transactions/${id}/reject`
    });
  }
}

function buildStockTransactionsQuery(query: ListStockTransactionsQuery): Record<string, string | number> {
  const result: Record<string, string | number> = {};

  if (query.page !== undefined) {
    result.page = query.page;
  }

  if (query.perPage !== undefined) {
    result.perPage = query.perPage;
  }

  if (query.q !== undefined) result.q = query.q;
  if (query.search !== undefined) result.search = query.search;
  if (query.sortBy !== undefined) result.sortBy = query.sortBy;
  if (query.sortDir !== undefined) result.sortDir = query.sortDir;
  if (query.type_id !== undefined) result.type_id = query.type_id;
  if (query.status_id !== undefined) result.status_id = query.status_id;
  if (query.transaction_date_from !== undefined) result.transaction_date_from = query.transaction_date_from;
  if (query.transaction_date_to !== undefined) result.transaction_date_to = query.transaction_date_to;
  if (query.created_at_from !== undefined) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== undefined) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== undefined) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== undefined) result.updated_at_to = query.updated_at_to;

  return result;
}
