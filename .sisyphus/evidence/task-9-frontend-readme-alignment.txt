- `dashboard`
- `reports`
- `stockOpnames`
| `sdk.auth.changePassword(payload)` | `PATCH /api/v1/auth/password` | authenticated |
| `sdk.users.changePassword(id, payload)` | `PATCH /api/v1/users/{id}/password` | `admin` only |
| `sdk.dailyPatients.list()` | `GET /api/v1/daily-patients` | `admin`, `gudang` |
| `sdk.dailyPatients.get(id)` | `GET /api/v1/daily-patients/{id}` | `admin`, `gudang` |
| `sdk.spk.postBasahStock(id)` | `POST /api/v1/spk/basah/history/{id}/post-stock` | `admin`, `gudang` |
| `sdk.spk.postKeringPengemasStock(id)` | `POST /api/v1/spk/kering-pengemas/history/{id}/post-stock` | `admin`, `gudang` |
### `dashboard` / `reports` / `stockOpnames`
| `dashboard` | `summary` | `admin`, `dapur`, `gudang` |
| `reports` | `stockReport`, `transactionLog` | `admin`, `gudang` |
| `stockOpnames` | `list`, `get`, `create`, `details` | `admin`, `gudang` |
