import { describe, expect, it, vi } from "vitest";

import { CapstoneSdk } from "..";

describe("ItemsResource", () => {
  it("serializes list query parameters using the backend names", async () => {
    const fetchMock = vi.fn<typeof fetch>().mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [],
          meta: { page: 1, perPage: 10, total: 0, totalPages: 0 },
          links: {
            self: "/api/v1/items?page=1&perPage=10",
            first: "/api/v1/items?page=1&perPage=10",
            last: "/api/v1/items?page=1&perPage=10",
            next: null,
            previous: null
          }
        }),
        {
          status: 200,
          headers: { "content-type": "application/json" }
        }
      )
    );

    const sdk = new CapstoneSdk({ fetchImplementation: fetchMock });

    await sdk.items.list({ page: 2, perPage: 20, item_category_id: 3, is_active: true, q: "beras" });

    const [url] = fetchMock.mock.calls[0] ?? [];

    expect(url).toBe(
      "http://127.0.0.1:8080/api/v1/items?page=2&perPage=20&item_category_id=3&is_active=true&q=beras"
    );
  });
});
