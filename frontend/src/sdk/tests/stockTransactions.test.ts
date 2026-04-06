import { describe, expect, it, vi } from "vitest";

import { CapstoneSdk } from "..";

describe("StockTransactionsResource", () => {
  it("posts revision payloads to the submit-revision workflow endpoint", async () => {
    const fetchMock = vi.fn<typeof fetch>().mockResolvedValue(
      new Response(
        JSON.stringify({
          message: "Revision submitted successfully.",
          data: { id: 12, approval_status_id: 2, is_revision: true, parent_transaction_id: 10 }
        }),
        {
          status: 201,
          headers: { "content-type": "application/json" }
        }
      )
    );

    const sdk = new CapstoneSdk({ fetchImplementation: fetchMock });

    await sdk.stockTransactions.submitRevision(10, {
      transaction_date: "2026-04-18",
      details: [{ item_id: 1, qty: 5000 }]
    });

    const [url, init] = fetchMock.mock.calls[0] ?? [];

    expect(url).toBe("http://127.0.0.1:8080/api/v1/stock-transactions/10/submit-revision");
    expect(init?.method).toBe("POST");
  });
});
