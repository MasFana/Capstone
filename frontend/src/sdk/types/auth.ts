import type { User } from "./users";

export interface LoginRequest {
  username: string;
  password: string;
}

export interface LoginResponse {
  message: string;
  access_token: string;
  token_type: "Bearer";
  user: User;
}
