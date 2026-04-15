import { describe, expect, it, vi } from "vitest";

import { CapstoneSdk } from "..";
import type {
  SpkBasahDetailResponse,
  SpkBasahHistoryListResponse,
  SpkKeringPengemasDetailResponse,
  SpkKeringPengemasHistoryListResponse
} from "../types";

describe("SpkResource", () => {
  it("calls basah generate/list/get endpoints with exact route and payload contracts", async () => {
    const fetchMock = vi.fn<typeof fetch>()
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            message: "SPK basah generated successfully.",
            data: {
              id: 10,
              version: 1,
              scope_key: "basah:2026-03-01:2026-03-02:1",
              target_dates: ["2026-03-01", "2026-03-02"],
              estimated_patients: 105
            }
          }),
          {
            status: 201,
            headers: { "content-type": "application/json" }
          }
        )
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            data: [
              {
                id: 10,
                version: 1,
                scope_key: "basah:2026-03-01:2026-03-02:1",
                is_latest: true,
                calculation_scope: "combined_window",
                calculation_date: "2026-03-01",
                target_date_start: "2026-03-01",
                target_date_end: "2026-03-02",
                target_month: null,
                estimated_patients: 105,
                is_finish: false,
                created_at: "2026-03-01 06:00:00",
                user: { id: 2, name: "Dapur User", username: "dapur" },
                category: { id: 1, name: "BASAH" }
              }
            ],
            meta: { total: 1 }
          }),
          {
            status: 200,
            headers: { "content-type": "application/json" }
          }
        )
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            data: {
              id: 10,
              version: 1,
              scope_key: "basah:2026-03-01:2026-03-02:1",
              is_latest: true,
              spk_type: "basah",
              calculation_scope: "combined_window",
              calculation_date: "2026-03-01",
              target_date_start: "2026-03-01",
              target_date_end: "2026-03-02",
              target_month: null,
              estimated_patients: 105,
              is_finish: false,
              created_at: "2026-03-01 06:00:00",
              updated_at: "2026-03-01 06:00:00",
              user: { id: 2, name: "Dapur User", username: "dapur" },
              category: { id: 1, name: "BASAH" },
              items: [
                {
                  id: 90,
                  item_id: 1,
                  item_name: "Ayam Basah",
                  item_unit_base: "gram",
                  item_unit_convert: "kg",
                  target_date: "2026-03-01",
                  current_stock_qty: 100,
                  required_qty: 210,
                  system_recommended_qty: 110,
                  final_recommended_qty: 110,
                  override: {
                    is_overridden: false,
                    reason: null,
                    overridden_by: null,
                    overridden_at: null
                  }
                }
              ],
              print_ready: {
                spk_id: 10,
                spk_type: "basah",
                version: 1,
                calculation_date: "2026-03-01",
                target_date_start: "2026-03-01",
                target_date_end: "2026-03-02",
                target_dates: ["2026-03-01", "2026-03-02"],
                estimated_patients: 105,
                category_name: "BASAH",
                generated_by: "Dapur User",
                recommendations: [
                  {
                    id: 90,
                    item_id: 1,
                    item_name: "Ayam Basah",
                    item_unit_base: "gram",
                    item_unit_convert: "kg",
                    target_date: "2026-03-01",
                    current_stock_qty: 100,
                    required_qty: 210,
                    system_recommended_qty: 110,
                    final_recommended_qty: 110,
                    override: {
                      is_overridden: false,
                      reason: null,
                      overridden_by: null,
                      overridden_at: null
                    }
                  }
                ]
              }
            }
          }),
          {
            status: 200,
            headers: { "content-type": "application/json" }
          }
        )
      );

    const sdk = new CapstoneSdk({ fetchImplementation: fetchMock });

    await sdk.spk.generateBasah({ service_date: "2026-03-01" });
    await sdk.spk.listBasah();
    await sdk.spk.getBasah(10);

    const [generateUrl, generateInit] = fetchMock.mock.calls[0] ?? [];
    const [listUrl, listInit] = fetchMock.mock.calls[1] ?? [];
    const [getUrl, getInit] = fetchMock.mock.calls[2] ?? [];

    expect(generateUrl).toBe("http://127.0.0.1:8080/api/v1/spk/basah/generate");
    expect(generateInit?.method).toBe("POST");
    expect(generateInit?.body).toBe(JSON.stringify({ service_date: "2026-03-01" }));
    expect(listUrl).toBe("http://127.0.0.1:8080/api/v1/spk/basah/history");
    expect(listInit?.method).toBe("GET");
    expect(getUrl).toBe("http://127.0.0.1:8080/api/v1/spk/basah/history/10");
    expect(getInit?.method).toBe("GET");
  });

  it("calls kering-pengemas generate/list/get endpoints with exact route and payload contracts", async () => {
    const fetchMock = vi.fn<typeof fetch>()
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            message: "SPK kering/pengemas generated successfully.",
            data: {
              id: 21,
              version: 2,
              scope_key: "kering_pengemas:2026-04:2",
              target_month: "2026-04"
            }
          }),
          {
            status: 201,
            headers: { "content-type": "application/json" }
          }
        )
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            data: [
              {
                id: 21,
                version: 2,
                scope_key: "kering_pengemas:2026-04:2",
                is_latest: true,
                calculation_scope: "monthly",
                calculation_date: "2026-04-01",
                target_date_start: "2026-04-01",
                target_date_end: "2026-04-30",
                target_month: "2026-04",
                estimated_patients: 0,
                is_finish: false,
                created_at: "2026-04-01 07:00:00",
                user: { id: 2, name: "Dapur User", username: "dapur" },
                category: { id: 2, name: "KERING" }
              }
            ],
            meta: { total: 1 }
          }),
          {
            status: 200,
            headers: { "content-type": "application/json" }
          }
        )
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            data: {
              id: 21,
              version: 2,
              scope_key: "kering_pengemas:2026-04:2",
              is_latest: true,
              spk_type: "kering_pengemas",
              calculation_scope: "monthly",
              calculation_date: "2026-04-01",
              target_date_start: "2026-04-01",
              target_date_end: "2026-04-30",
              target_month: "2026-04",
              estimated_patients: 0,
              is_finish: false,
              created_at: "2026-04-01 07:00:00",
              updated_at: "2026-04-01 07:00:00",
              user: { id: 2, name: "Dapur User", username: "dapur" },
              category: { id: 2, name: "KERING" },
              items: [
                {
                  id: 120,
                  item_id: 1,
                  item_name: "Beras Kering",
                  item_unit_base: "gram",
                  item_unit_convert: "kg",
                  target_date: null,
                  current_stock_qty: 80,
                  required_qty: 550,
                  system_recommended_qty: 470,
                  final_recommended_qty: 470,
                  override: {
                    is_overridden: false,
                    reason: null,
                    overridden_by: null,
                    overridden_at: null
                  }
                }
              ],
              print_ready: {
                spk_id: 21,
                spk_type: "kering_pengemas",
                version: 2,
                calculation_date: "2026-04-01",
                target_date_start: "2026-04-01",
                target_date_end: "2026-04-30",
                target_month: "2026-04",
                estimated_patients: 0,
                category_name: "KERING",
                generated_by: "Dapur User",
                recommendations: [
                  {
                    id: 120,
                    item_id: 1,
                    item_name: "Beras Kering",
                    item_unit_base: "gram",
                    item_unit_convert: "kg",
                    target_date: null,
                    current_stock_qty: 80,
                    required_qty: 550,
                    system_recommended_qty: 470,
                    final_recommended_qty: 470,
                    override: {
                      is_overridden: false,
                      reason: null,
                      overridden_by: null,
                      overridden_at: null
                    }
                  }
                ]
              }
            }
          }),
          {
            status: 200,
            headers: { "content-type": "application/json" }
          }
        )
      );

    const sdk = new CapstoneSdk({ fetchImplementation: fetchMock });

    await sdk.spk.generateKeringPengemas({ target_month: "2026-04" });
    await sdk.spk.listKeringPengemas();
    await sdk.spk.getKeringPengemas(21);

    const [generateUrl, generateInit] = fetchMock.mock.calls[0] ?? [];
    const [listUrl, listInit] = fetchMock.mock.calls[1] ?? [];
    const [getUrl, getInit] = fetchMock.mock.calls[2] ?? [];

    expect(generateUrl).toBe("http://127.0.0.1:8080/api/v1/spk/kering-pengemas/generate");
    expect(generateInit?.method).toBe("POST");
    expect(generateInit?.body).toBe(JSON.stringify({ target_month: "2026-04" }));
    expect(listUrl).toBe("http://127.0.0.1:8080/api/v1/spk/kering-pengemas/history");
    expect(listInit?.method).toBe("GET");
    expect(getUrl).toBe("http://127.0.0.1:8080/api/v1/spk/kering-pengemas/history/21");
    expect(getInit?.method).toBe("GET");
  });

  it("keeps basah and kering/pengemas detail DTO contracts distinct for drift detection", () => {
    const basahList: SpkBasahHistoryListResponse = {
      data: [
        {
          id: 1,
          version: 1,
          scope_key: "basah:2026-03-01:2026-03-02:1",
          is_latest: true,
          calculation_scope: "combined_window",
          calculation_date: "2026-03-01",
          target_date_start: "2026-03-01",
          target_date_end: "2026-03-02",
          target_month: null,
          estimated_patients: 105,
          is_finish: false,
          created_at: "2026-03-01 06:00:00",
          user: { id: 2, name: "Dapur User", username: "dapur" },
          category: { id: 1, name: "BASAH" }
        }
      ],
      meta: { total: 1 }
    };

    const keringList: SpkKeringPengemasHistoryListResponse = {
      data: [
        {
          id: 2,
          version: 1,
          scope_key: "kering_pengemas:2026-04:2",
          is_latest: true,
          calculation_scope: "monthly",
          calculation_date: "2026-04-01",
          target_date_start: "2026-04-01",
          target_date_end: "2026-04-30",
          target_month: "2026-04",
          estimated_patients: 0,
          is_finish: false,
          created_at: "2026-04-01 07:00:00",
          user: { id: 2, name: "Dapur User", username: "dapur" },
          category: { id: 2, name: "KERING" }
        }
      ],
      meta: { total: 1 }
    };

    const basahDetail: SpkBasahDetailResponse = {
      data: {
        id: 1,
        version: 1,
        scope_key: "basah:2026-03-01:2026-03-02:1",
        is_latest: true,
        spk_type: "basah",
        calculation_scope: "combined_window",
        calculation_date: "2026-03-01",
        target_date_start: "2026-03-01",
        target_date_end: "2026-03-02",
        target_month: null,
        estimated_patients: 105,
        is_finish: false,
        created_at: "2026-03-01 06:00:00",
        updated_at: "2026-03-01 06:00:00",
        user: { id: 2, name: "Dapur User", username: "dapur" },
        category: { id: 1, name: "BASAH" },
        items: [
          {
            id: 100,
            item_id: 1,
            item_name: "Ayam Basah",
            item_unit_base: "gram",
            item_unit_convert: "kg",
            target_date: "2026-03-01",
            current_stock_qty: 100,
            required_qty: 210,
            system_recommended_qty: 110,
            final_recommended_qty: 110,
            override: {
              is_overridden: false,
              reason: null,
              overridden_by: null,
              overridden_at: null
            }
          }
        ],
        print_ready: {
          spk_id: 1,
          spk_type: "basah",
          version: 1,
          calculation_date: "2026-03-01",
          target_date_start: "2026-03-01",
          target_date_end: "2026-03-02",
          target_dates: ["2026-03-01", "2026-03-02"],
          estimated_patients: 105,
          category_name: "BASAH",
          generated_by: "Dapur User",
          recommendations: []
        }
      }
    };

    const keringDetail: SpkKeringPengemasDetailResponse = {
      data: {
        id: 2,
        version: 1,
        scope_key: "kering_pengemas:2026-04:2",
        is_latest: true,
        spk_type: "kering_pengemas",
        calculation_scope: "monthly",
        calculation_date: "2026-04-01",
        target_date_start: "2026-04-01",
        target_date_end: "2026-04-30",
        target_month: "2026-04",
        estimated_patients: 0,
        is_finish: false,
        created_at: "2026-04-01 07:00:00",
        updated_at: "2026-04-01 07:00:00",
        user: { id: 2, name: "Dapur User", username: "dapur" },
        category: { id: 2, name: "KERING" },
        items: [
          {
            id: 101,
            item_id: 2,
            item_name: "Beras Kering",
            item_unit_base: "gram",
            item_unit_convert: "kg",
            target_date: null,
            current_stock_qty: 80,
            required_qty: 550,
            system_recommended_qty: 470,
            final_recommended_qty: 470,
            override: {
              is_overridden: false,
              reason: null,
              overridden_by: null,
              overridden_at: null
            }
          }
        ],
        print_ready: {
          spk_id: 2,
          spk_type: "kering_pengemas",
          version: 1,
          calculation_date: "2026-04-01",
          target_date_start: "2026-04-01",
          target_date_end: "2026-04-30",
          target_month: "2026-04",
          estimated_patients: 0,
          category_name: "KERING",
          generated_by: "Dapur User",
          recommendations: []
        }
      }
    };

    expect(basahList.meta.total).toBe(1);
    expect(keringList.meta.total).toBe(1);

    expect(basahDetail.data.print_ready.target_dates).toEqual(["2026-03-01", "2026-03-02"]);
    expect(keringDetail.data.print_ready.target_month).toBe("2026-04");

    expect(basahDetail.data.items[0]?.target_date).toBe("2026-03-01");
    expect(keringDetail.data.items[0]?.target_date).toBeNull();
  });

  it("does not expose ambiguous merged SPK APIs", () => {
    const sdk = new CapstoneSdk({});

    expect("generate" in sdk.spk).toBe(false);
    expect("list" in sdk.spk).toBe(false);
    expect("get" in sdk.spk).toBe(false);
  });
});
