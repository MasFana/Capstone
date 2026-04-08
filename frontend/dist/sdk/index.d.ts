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
export declare class CapstoneSdk {
    readonly client: ApiClient;
    readonly auth: AuthResource;
    readonly roles: RolesResource;
    readonly items: ItemsResource;
    readonly stockTransactions: StockTransactionsResource;
    readonly users: UsersResource;
    constructor(options: ApiClientOptions);
    /**
     * Updates the in-memory bearer token used by the shared client.
     */
    setAccessToken(token: string | null): void;
    /**
     * Clears the in-memory bearer token used by the shared client.
     */
    clearAccessToken(): void;
}
/**
 * Creates a configured SDK instance for the Capstone API.
 */
export declare function createCapstoneSdk(options: ApiClientOptions): CapstoneSdk;
