export * from "./client";
export * from "./errors";
export * from "./resources/auth";
export * from "./resources/approvalStatuses";
export * from "./resources/itemCategories";
export * from "./resources/items";
export * from "./resources/itemUnits";
export * from "./resources/roles";
export * from "./resources/stockTransactions";
export * from "./resources/transactionTypes";
export * from "./resources/users";
export * from "./types";

import { ApiClient, type ApiClientOptions } from "./client";
import { ApprovalStatusesResource } from "./resources/approvalStatuses";
import { AuthResource } from "./resources/auth";
import { ItemCategoriesResource } from "./resources/itemCategories";
import { ItemsResource } from "./resources/items";
import { ItemUnitsResource } from "./resources/itemUnits";
import { RolesResource } from "./resources/roles";
import { StockTransactionsResource } from "./resources/stockTransactions";
import { TransactionTypesResource } from "./resources/transactionTypes";
import { UsersResource } from "./resources/users";

/**
 * High-level SDK entry point for the current Capstone API surface.
 */
export class CapstoneSdk {
  public readonly client: ApiClient;
  public readonly approvalStatuses: ApprovalStatusesResource;
  public readonly auth: AuthResource;
  public readonly itemCategories: ItemCategoriesResource;
  public readonly roles: RolesResource;
  public readonly items: ItemsResource;
  public readonly itemUnits: ItemUnitsResource;
  public readonly stockTransactions: StockTransactionsResource;
  public readonly transactionTypes: TransactionTypesResource;
  public readonly users: UsersResource;

  public constructor(options: ApiClientOptions) {
    this.client = new ApiClient(options);
    this.approvalStatuses = new ApprovalStatusesResource(this.client);
    this.auth = new AuthResource(this.client);
    this.itemCategories = new ItemCategoriesResource(this.client);
    this.roles = new RolesResource(this.client);
    this.items = new ItemsResource(this.client);
    this.itemUnits = new ItemUnitsResource(this.client);
    this.stockTransactions = new StockTransactionsResource(this.client);
    this.transactionTypes = new TransactionTypesResource(this.client);
    this.users = new UsersResource(this.client);
  }

  /**
   * Updates the in-memory bearer token used by the shared client.
   */
  public setAccessToken(token: string | null): void {
    this.client.setAccessToken(token);
  }

  /**
   * Clears the in-memory bearer token used by the shared client.
   */
  public clearAccessToken(): void {
    this.client.clearAccessToken();
  }
}

/**
 * Creates a configured SDK instance for the Capstone API.
 */
export function createCapstoneSdk(options: ApiClientOptions): CapstoneSdk {
  return new CapstoneSdk(options);
}
