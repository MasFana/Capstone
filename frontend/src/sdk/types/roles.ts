export type RoleName = "admin" | "dapur" | "gudang";

export interface Role {
  id: number;
  name: RoleName | string;
}
