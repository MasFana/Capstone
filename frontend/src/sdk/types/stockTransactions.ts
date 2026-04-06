import type { XOR } from "./common";

export type TransactionTypeName = "IN" | "OUT" | "RETURN_IN";

export interface StockTransaction {
  id: number;
  type_id: number;
  transaction_date: string;
  is_revision: boolean;
  parent_transaction_id: number | null;
  approval_status_id: number;
  approved_by: number | null;
  user_id: number;
  spk_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface StockTransactionDetail {
  id: number;
  transaction_id: number;
  item_id: number;
  qty: string;
}

export interface StockTransactionDetailInput {
  item_id: number;
  qty: number;
}

type TransactionTypeIdentifier = XOR<{ type_id: number }, { type_name: TransactionTypeName | string }>;

export interface ListStockTransactionsQuery {
  page?: number;
  perPage?: number;
}

export type CreateStockTransactionRequest = TransactionTypeIdentifier & {
  transaction_date: string;
  spk_id?: number | null;
  details: StockTransactionDetailInput[];
};

export interface SubmitRevisionRequest {
  transaction_date: string;
  spk_id?: number | null;
  details: StockTransactionDetailInput[];
}

export interface StockTransactionCreateResult {
  id: number;
  approval_status_id: number;
  is_revision: boolean;
}

export interface StockTransactionRevisionResult extends StockTransactionCreateResult {
  parent_transaction_id: number;
}

export interface StockTransactionModerationResult {
  id: number;
  approval_status_id: number;
  approved_by: number;
}
