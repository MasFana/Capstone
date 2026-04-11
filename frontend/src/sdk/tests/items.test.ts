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

    await sdk.items.list({
      page: 2,
      perPage: 20,
      item_category_id: 3,
      is_active: true,
      q: "beras",
      sortBy: "updated_at",
      sortDir: "DESC",
      created_at_from: "2026-04-01",
      updated_at_to: "2026-04-30"
    });

    const [url] = fetchMock.mock.calls[0] ?? [];

    expect(url).toBe(
      "http://127.0.0.1:8080/api/v1/items?page=2&perPage=20&item_category_id=3&is_active=true&q=beras&sortBy=updated_at&sortDir=DESC&created_at_from=2026-04-01&updated_at_to=2026-04-30"
    );
  });

  it("tracks item unit identifiers in item responses", () => {
    const item = {
      id: 1,
      item_category_id: 2,
      name: "Beras",
      unit_base: "gram",
      unit_convert: "kg",
      item_unit_base_id: 1,
      item_unit_convert_id: 2,
      conversion_base: 1000,
      qty: "100.00",
      is_active: true,
      created_at: "2026-04-01 10:00:00",
      updated_at: "2026-04-01 10:00:00",
      category: { id: 2, name: "KERING" },
      item_unit_base: { id: 1, name: "gram" },
      item_unit_convert: { id: 2, name: "kg" }
    };

    expect(item.item_unit_base_id).toBe(1);
    expect(item.item_unit_convert?.name).toBe("kg");
  });
});
