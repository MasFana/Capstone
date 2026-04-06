import type { XOR } from "./common";
import type { Role } from "./roles";

export interface User {
  id: number;
  role_id: number;
  name: string;
  username: string;
  email: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  role?: Role;
}

type UserRoleIdentifier = XOR<{ role_id: number }, { role_name: string }>;
type OptionalUserRoleIdentifier = UserRoleIdentifier | { role_id?: undefined; role_name?: undefined };

export type CreateUserRequest = UserRoleIdentifier & {
  name: string;
  username: string;
  password: string;
  email?: string;
  is_active?: boolean;
};

export type UpdateUserRequest = OptionalUserRoleIdentifier & {
  name?: string;
  username?: string;
  email?: string;
  is_active?: boolean;
};

export interface ChangePasswordRequest {
  password: string;
}
