"use strict";
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};
var __copyProps = (to, from, except, desc) => {
  if (from && typeof from === "object" || typeof from === "function") {
    for (let key of __getOwnPropNames(from))
      if (!__hasOwnProp.call(to, key) && key !== except)
        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  }
  return to;
};
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// src/index.ts
var index_exports = {};
__export(index_exports, {
  ApiClient: () => ApiClient,
  ApiError: () => ApiError,
  ApprovalStatusesResource: () => ApprovalStatusesResource,
  AuthResource: () => AuthResource,
  AuthenticationApiError: () => AuthenticationApiError,
  AuthorizationApiError: () => AuthorizationApiError,
  CapstoneSdk: () => CapstoneSdk,
  ItemCategoriesResource: () => ItemCategoriesResource,
  ItemUnitsResource: () => ItemUnitsResource,
  ItemsResource: () => ItemsResource,
  NotFoundApiError: () => NotFoundApiError,
  RolesResource: () => RolesResource,
  StockTransactionsResource: () => StockTransactionsResource,
  TransactionTypesResource: () => TransactionTypesResource,
  UsersResource: () => UsersResource,
  ValidationApiError: () => ValidationApiError,
  createCapstoneSdk: () => createCapstoneSdk,
  toApiError: () => toApiError
});
module.exports = __toCommonJS(index_exports);

// src/sdk/errors.ts
var ApiError = class extends Error {
  status;
  body;
  constructor(message, status, body) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.body = body;
  }
};
var ValidationApiError = class extends ApiError {
  constructor(body, status = 400) {
    super(body.message, status, body);
    this.name = "ValidationApiError";
  }
  get errors() {
    return this.body?.errors ?? {};
  }
};
var AuthenticationApiError = class extends ApiError {
  constructor(body, status = 401) {
    super(body?.message ?? "Authentication failed.", status, body);
    this.name = "AuthenticationApiError";
  }
};
var AuthorizationApiError = class extends ApiError {
  constructor(body, status = 403) {
    super(body?.message ?? "Authorization failed.", status, body);
    this.name = "AuthorizationApiError";
  }
};
var NotFoundApiError = class extends ApiError {
  constructor(body, status = 404) {
    super(body?.message ?? "Resource not found.", status, body);
    this.name = "NotFoundApiError";
  }
};
function toApiError(status, body) {
  const normalized = isApiErrorResponse(body) ? body : null;
  if (status === 400 && isValidationErrorResponse(body)) {
    return new ValidationApiError(body, status);
  }
  if (status === 401) {
    return new AuthenticationApiError(normalized, status);
  }
  if (status === 403) {
    return new AuthorizationApiError(normalized, status);
  }
  if (status === 404) {
    return new NotFoundApiError(normalized, status);
  }
  return new ApiError(normalized?.message ?? `Request failed with status ${status}.`, status, normalized);
}
function isApiErrorResponse(value) {
  return typeof value === "object" && value !== null && "message" in value && typeof value.message === "string";
}
function isValidationErrorResponse(value) {
  return typeof value === "object" && value !== null && "message" in value && typeof value.message === "string" && "errors" in value && typeof value.errors === "object" && value.errors !== null && !Array.isArray(value.errors);
}

// src/sdk/client.ts
var ApiClient = class {
  baseUrl;
  apiBasePath;
  defaultHeaders;
  fetchImplementation;
  getAccessToken;
  accessToken;
  constructor(options = {}) {
    this.baseUrl = trimTrailingSlash(options.baseUrl ?? "http://127.0.0.1:8080");
    this.apiBasePath = ensureLeadingSlash(trimTrailingSlash(options.apiBasePath ?? "/api/v1"));
    this.defaultHeaders = options.defaultHeaders ?? {};
    this.fetchImplementation = options.fetchImplementation ?? globalThis.fetch;
    this.getAccessToken = options.getAccessToken;
    this.accessToken = options.accessToken ?? null;
    if (typeof this.fetchImplementation !== "function") {
      throw new Error("A fetch implementation is required to use the API client.");
    }
  }
  setAccessToken(token) {
    this.accessToken = token;
  }
  clearAccessToken() {
    this.accessToken = null;
  }
  async request(options) {
    const headers = new Headers(this.defaultHeaders);
    for (const [key, value] of new Headers(options.headers).entries()) {
      headers.set(key, value);
    }
    headers.set("Accept", "application/json");
    const token = await this.resolveAccessToken();
    if (token) {
      headers.set("Authorization", `Bearer ${token}`);
    }
    let body;
    if (options.body !== void 0) {
      headers.set("Content-Type", "application/json");
      body = JSON.stringify(options.body);
    }
    const requestInit = {
      method: options.method ?? "GET",
      headers
    };
    if (body !== void 0) {
      requestInit.body = body;
    }
    const response = await this.fetchImplementation(this.buildUrl(options.path, options.query), requestInit);
    const payload = await parseResponse(response);
    if (!response.ok) {
      throw toApiError(response.status, payload);
    }
    return payload;
  }
  buildUrl(path, query) {
    const normalizedPath = ensureLeadingSlash(path);
    const url = new URL(`${this.baseUrl}${this.apiBasePath}${normalizedPath}`);
    if (query) {
      for (const [key, value] of Object.entries(query)) {
        if (value === void 0 || value === null) {
          continue;
        }
        url.searchParams.set(key, String(value));
      }
    }
    return url.toString();
  }
  async resolveAccessToken() {
    const dynamicToken = await this.getAccessToken?.();
    if (dynamicToken !== void 0) {
      return dynamicToken ?? null;
    }
    return this.accessToken;
  }
};
async function parseResponse(response) {
  if (response.status === 204) {
    return void 0;
  }
  const contentType = response.headers.get("content-type") ?? "";
  if (contentType.includes("application/json")) {
    return response.json();
  }
  const text = await response.text();
  return text.length > 0 ? text : void 0;
}
function trimTrailingSlash(value) {
  return value.replace(/\/+$/, "");
}
function ensureLeadingSlash(value) {
  return value.startsWith("/") ? value : `/${value}`;
}

// src/sdk/resources/auth.ts
var AuthResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  /**
   * Logs a user in.
   *
   * HTTP: `POST /api/v1/auth/login`
   * Access: public
   */
  login(payload) {
    return this.client.request({
      method: "POST",
      path: "/auth/login",
      body: payload
    });
  }
  /**
   * Returns the current authenticated user.
   *
   * HTTP: `GET /api/v1/auth/me`
   * Access: authenticated `admin`, `dapur`, `gudang`
   */
  me() {
    return this.client.request({
      method: "GET",
      path: "/auth/me"
    });
  }
  /**
   * Revokes the current access token.
   *
   * HTTP: `POST /api/v1/auth/logout`
   * Access: authenticated `admin`, `dapur`, `gudang`
   */
  logout() {
    return this.client.request({
      method: "POST",
      path: "/auth/logout"
    });
  }
};

// src/sdk/resources/approvalStatuses.ts
var ApprovalStatusesResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/approval-statuses",
      ...query ? { query: buildLookupQuery(query) } : {}
    });
  }
};
function buildLookupQuery(query) {
  const result = {};
  if (query.paginate !== void 0) result.paginate = query.paginate ? "true" : "false";
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/itemCategories.ts
var ItemCategoriesResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/item-categories",
      ...query ? { query: buildLookupQuery2(query) } : {}
    });
  }
  get(id) {
    return this.client.request({
      method: "GET",
      path: `/item-categories/${id}`
    });
  }
  create(payload) {
    return this.client.request({
      method: "POST",
      path: "/item-categories",
      body: payload
    });
  }
  update(id, payload) {
    return this.client.request({
      method: "PUT",
      path: `/item-categories/${id}`,
      body: payload
    });
  }
  delete(id) {
    return this.client.request({
      method: "DELETE",
      path: `/item-categories/${id}`
    });
  }
  restore(id) {
    return this.client.request({
      method: "PATCH",
      path: `/item-categories/${id}/restore`
    });
  }
};
function buildLookupQuery2(query) {
  const result = {};
  if (query.paginate !== void 0) result.paginate = query.paginate ? "true" : "false";
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/items.ts
var ItemsResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  /**
   * Lists items with pagination, filtering, and search.
   *
   * HTTP: `GET /api/v1/items`
   * Access: `admin`, `gudang`
   */
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/items",
      ...query ? { query: buildItemsQuery(query) } : {}
    });
  }
  /**
   * Returns a single item by identifier.
   *
   * HTTP: `GET /api/v1/items/{id}`
   * Access: `admin`, `gudang`
   */
  get(id) {
    return this.client.request({
      method: "GET",
      path: `/items/${id}`
    });
  }
  /**
   * Creates a new item.
   *
   * HTTP: `POST /api/v1/items`
   * Access: `admin`, `gudang`
   */
  create(payload) {
    return this.client.request({
      method: "POST",
      path: "/items",
      body: payload
    });
  }
  /**
   * Updates an existing item using the backend's partial-update semantics.
   *
   * HTTP: `PUT /api/v1/items/{id}`
   * Access: `admin`, `gudang`
   */
  update(id, payload) {
    return this.client.request({
      method: "PUT",
      path: `/items/${id}`,
      body: payload
    });
  }
  /**
   * Soft-deletes an item.
   *
   * HTTP: `DELETE /api/v1/items/{id}`
   * Access: `admin` only
   */
  delete(id) {
    return this.client.request({
      method: "DELETE",
      path: `/items/${id}`
    });
  }
  /**
   * Restores a soft-deleted item.
   *
   * Idempotent: if the item is already active, returns 200 with current data.
   * Returns 400 if an active item with the same name already exists.
   * Returns 400 if the referenced category or units are no longer active.
   * Returns 404 if the item does not exist at all.
   *
   * HTTP: `PATCH /api/v1/items/{id}/restore`
   * Access: `admin` only
   */
  restore(id) {
    return this.client.request({
      method: "PATCH",
      path: `/items/${id}/restore`
    });
  }
};
function buildItemsQuery(query) {
  const result = {};
  if (query.page !== void 0) {
    result.page = query.page;
  }
  if (query.perPage !== void 0) {
    result.perPage = query.perPage;
  }
  if (query.item_category_id !== void 0) {
    result.item_category_id = query.item_category_id;
  }
  if (query.is_active !== void 0) {
    result.is_active = query.is_active;
  }
  if (query.q !== void 0) {
    result.q = query.q;
  }
  if (query.search !== void 0) {
    result.search = query.search;
  }
  if (query.sortBy !== void 0) {
    result.sortBy = query.sortBy;
  }
  if (query.sortDir !== void 0) {
    result.sortDir = query.sortDir;
  }
  if (query.created_at_from !== void 0) {
    result.created_at_from = query.created_at_from;
  }
  if (query.created_at_to !== void 0) {
    result.created_at_to = query.created_at_to;
  }
  if (query.updated_at_from !== void 0) {
    result.updated_at_from = query.updated_at_from;
  }
  if (query.updated_at_to !== void 0) {
    result.updated_at_to = query.updated_at_to;
  }
  return result;
}

// src/sdk/resources/itemUnits.ts
var ItemUnitsResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/item-units",
      ...query ? { query: buildLookupQuery3(query) } : {}
    });
  }
  get(id) {
    return this.client.request({
      method: "GET",
      path: `/item-units/${id}`
    });
  }
  create(payload) {
    return this.client.request({
      method: "POST",
      path: "/item-units",
      body: payload
    });
  }
  update(id, payload) {
    return this.client.request({
      method: "PUT",
      path: `/item-units/${id}`,
      body: payload
    });
  }
  delete(id) {
    return this.client.request({
      method: "DELETE",
      path: `/item-units/${id}`
    });
  }
  restore(id) {
    return this.client.request({
      method: "PATCH",
      path: `/item-units/${id}/restore`
    });
  }
};
function buildLookupQuery3(query) {
  const result = {};
  if (query.paginate !== void 0) result.paginate = query.paginate ? "true" : "false";
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/roles.ts
var RolesResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  /**
   * Lists all available roles.
   *
   * HTTP: `GET /api/v1/roles`
   * Access: `admin` only
   */
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/roles",
      ...query ? { query: buildRoleQuery(query) } : {}
    });
  }
};
function buildRoleQuery(query) {
  const result = {};
  if (query.paginate !== void 0) result.paginate = query.paginate ? "true" : "false";
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/stockTransactions.ts
var StockTransactionsResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  /**
   * Lists stock transactions with pagination.
   *
   * HTTP: `GET /api/v1/stock-transactions`
   * Access: `admin`, `gudang`
   */
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/stock-transactions",
      ...query ? { query: buildStockTransactionsQuery(query) } : {}
    });
  }
  /**
   * Returns a stock transaction header.
   *
   * HTTP: `GET /api/v1/stock-transactions/{id}`
   * Access: `admin`, `gudang`
   */
  get(id) {
    return this.client.request({
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
  details(id) {
    return this.client.request({
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
  create(payload) {
    return this.client.request({
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
  directCorrection(payload) {
    return this.client.request({
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
  submitRevision(id, payload) {
    return this.client.request({
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
  approve(id) {
    return this.client.request({
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
  reject(id) {
    return this.client.request({
      method: "POST",
      path: `/stock-transactions/${id}/reject`
    });
  }
};
function buildStockTransactionsQuery(query) {
  const result = {};
  if (query.page !== void 0) {
    result.page = query.page;
  }
  if (query.perPage !== void 0) {
    result.perPage = query.perPage;
  }
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.type_id !== void 0) result.type_id = query.type_id;
  if (query.status_id !== void 0) result.status_id = query.status_id;
  if (query.transaction_date_from !== void 0) result.transaction_date_from = query.transaction_date_from;
  if (query.transaction_date_to !== void 0) result.transaction_date_to = query.transaction_date_to;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/transactionTypes.ts
var TransactionTypesResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/transaction-types",
      ...query ? { query: buildLookupQuery4(query) } : {}
    });
  }
};
function buildLookupQuery4(query) {
  const result = {};
  if (query.paginate !== void 0) result.paginate = query.paginate ? "true" : "false";
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/resources/users.ts
var UsersResource = class {
  constructor(client) {
    this.client = client;
  }
  client;
  /**
   * Lists all users.
   *
   * HTTP: `GET /api/v1/users`
   * Access: `admin` only
   */
  list(query) {
    return this.client.request({
      method: "GET",
      path: "/users",
      ...query ? { query: buildUsersQuery(query) } : {}
    });
  }
  /**
   * Returns a single user by identifier.
   *
   * HTTP: `GET /api/v1/users/{id}`
   * Access: `admin` only
   */
  get(id) {
    return this.client.request({
      method: "GET",
      path: `/users/${id}`
    });
  }
  /**
   * Creates a new user.
   *
   * HTTP: `POST /api/v1/users`
   * Access: `admin` only
   */
  create(payload) {
    return this.client.request({
      method: "POST",
      path: "/users",
      body: payload
    });
  }
  /**
   * Updates a user profile and role assignment.
   *
   * HTTP: `PUT /api/v1/users/{id}`
   * Access: `admin` only
   */
  update(id, payload) {
    return this.client.request({
      method: "PUT",
      path: `/users/${id}`,
      body: payload
    });
  }
  /**
   * Activates a user account.
   *
   * HTTP: `PATCH /api/v1/users/{id}/activate`
   * Access: `admin` only
   */
  activate(id) {
    return this.client.request({
      method: "PATCH",
      path: `/users/${id}/activate`
    });
  }
  /**
   * Deactivates a user account.
   *
   * HTTP: `PATCH /api/v1/users/{id}/deactivate`
   * Access: `admin` only
   */
  deactivate(id) {
    return this.client.request({
      method: "PATCH",
      path: `/users/${id}/deactivate`
    });
  }
  /**
   * Changes a user's password.
   *
   * HTTP: `PATCH /api/v1/users/{id}/password`
   * Access: `admin` only
   */
  changePassword(id, payload) {
    return this.client.request({
      method: "PATCH",
      path: `/users/${id}/password`,
      body: payload
    });
  }
  /**
   * Soft-deletes a user.
   *
   * HTTP: `DELETE /api/v1/users/{id}`
   * Access: `admin` only
   */
  delete(id) {
    return this.client.request({
      method: "DELETE",
      path: `/users/${id}`
    });
  }
  /**
   * Restores a soft-deleted user.
   *
   * Idempotent: if the user is already active, returns 200 with current data.
   * Returns 400 if an active user with the same username already exists.
   * Returns 400 if the assigned role is no longer active.
   * Returns 404 if the user does not exist at all.
   *
   * HTTP: `PATCH /api/v1/users/{id}/restore`
   * Access: `admin` only
   */
  restore(id) {
    return this.client.request({
      method: "PATCH",
      path: `/users/${id}/restore`
    });
  }
};
function buildUsersQuery(query) {
  const result = {};
  if (query.page !== void 0) result.page = query.page;
  if (query.perPage !== void 0) result.perPage = query.perPage;
  if (query.q !== void 0) result.q = query.q;
  if (query.search !== void 0) result.search = query.search;
  if (query.sortBy !== void 0) result.sortBy = query.sortBy;
  if (query.sortDir !== void 0) result.sortDir = query.sortDir;
  if (query.role_id !== void 0) result.role_id = query.role_id;
  if (query.is_active !== void 0) result.is_active = query.is_active;
  if (query.created_at_from !== void 0) result.created_at_from = query.created_at_from;
  if (query.created_at_to !== void 0) result.created_at_to = query.created_at_to;
  if (query.updated_at_from !== void 0) result.updated_at_from = query.updated_at_from;
  if (query.updated_at_to !== void 0) result.updated_at_to = query.updated_at_to;
  return result;
}

// src/sdk/index.ts
var CapstoneSdk = class {
  client;
  approvalStatuses;
  auth;
  itemCategories;
  roles;
  items;
  itemUnits;
  stockTransactions;
  transactionTypes;
  users;
  constructor(options) {
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
  setAccessToken(token) {
    this.client.setAccessToken(token);
  }
  /**
   * Clears the in-memory bearer token used by the shared client.
   */
  clearAccessToken() {
    this.client.clearAccessToken();
  }
};
function createCapstoneSdk(options) {
  return new CapstoneSdk(options);
}
