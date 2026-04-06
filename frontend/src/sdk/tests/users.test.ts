import { describe, expect, it, vi } from "vitest";

import { CapstoneSdk } from "..";

describe("UsersResource", () => {
  it("targets the password change endpoint with PATCH", async () => {
    const fetchMock = vi.fn<typeof fetch>().mockResolvedValue(
      new Response(JSON.stringify({ message: "Password changed successfully. All access tokens have been revoked." }), {
        status: 200,
        headers: { "content-type": "application/json" }
      })
    );

    const sdk = new CapstoneSdk({ fetchImplementation: fetchMock });

    await sdk.users.changePassword(4, { password: "newpassword123" });

    const [url, init] = fetchMock.mock.calls[0] ?? [];

    expect(url).toBe("http://127.0.0.1:8080/api/v1/users/4/password");
    expect(init?.method).toBe("PATCH");
  });
});
