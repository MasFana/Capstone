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
export declare class CapstoneSdk {
    readonly client: ApiClient;
    readonly approvalStatuses: ApprovalStatusesResource;
    readonly auth: AuthResource;
    readonly itemCategories: ItemCategoriesResource;
    readonly roles: RolesResource;
    readonly items: ItemsResource;
    readonly itemUnits: ItemUnitsResource;
    readonly stockTransactions: StockTransactionsResource;
    readonly transactionTypes: TransactionTypesResource;
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
