import type { ApiClient } from "../client";
import type {
  ApiMessageDataResponse,
  ApiMessageResponse,
  CreateMenuSlotRequest,
  MenuSlot,
  MenuSlotsListResponse,
  MenusListResponse,
  UpdateMenuSlotRequest
} from "../types";

export class MenusResource {
  public constructor(private readonly client: ApiClient) {}

  public list(): Promise<MenusListResponse> {
    return this.client.request<MenusListResponse>({
      method: "GET",
      path: "/menus"
    });
  }

  public slots(): Promise<MenuSlotsListResponse> {
    return this.client.request<MenuSlotsListResponse>({
      method: "GET",
      path: "/menu-dishes"
    });
  }

  public assignSlot(payload: CreateMenuSlotRequest): Promise<ApiMessageDataResponse<MenuSlot>> {
    return this.client.request<ApiMessageDataResponse<MenuSlot>>({
      method: "POST",
      path: "/menu-dishes",
      body: payload
    });
  }

  public updateSlot(id: number, payload: UpdateMenuSlotRequest): Promise<ApiMessageDataResponse<MenuSlot>> {
    return this.client.request<ApiMessageDataResponse<MenuSlot>>({
      method: "PUT",
      path: `/menu-dishes/${id}`,
      body: payload
    });
  }

  public deleteSlot(id: number): Promise<ApiMessageResponse> {
    return this.client.request<ApiMessageResponse>({
      method: "DELETE",
      path: `/menu-dishes/${id}`
    });
  }
}
