export * from "./client";
export * from "./errors";
export * from "./resources/auth";
export * from "./resources/items";
export * from "./resources/roles";
export * from "./resources/stockTransactions";
export * from "./resources/users";
export * from "./types";

import { ApiClient, type ApiClientOptions } from "./client";
import { AuthResource } from "./resources/auth";
import { ItemsResource } from "./resources/items";
import { RolesResource } from "./resources/roles";
import { StockTransactionsResource } from "./resources/stockTransactions";
import { UsersResource } from "./resources/users";

/**
 * High-level SDK entry point for the current Capstone API surface.
 */
export class CapstoneSdk {
  public readonly client: ApiClient;
  public readonly auth: AuthResource;
  public readonly roles: RolesResource;
  public readonly items: ItemsResource;
  public readonly stockTransactions: StockTransactionsResource;
  public readonly users: UsersResource;

  public constructor(options: ApiClientOptions) {
    this.client = new ApiClient(options);
    this.auth = new AuthResource(this.client);
    this.roles = new RolesResource(this.client);
    this.items = new ItemsResource(this.client);
    this.stockTransactions = new StockTransactionsResource(this.client);
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
