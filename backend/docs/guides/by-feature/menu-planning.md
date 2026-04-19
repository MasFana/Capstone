# Menu Planning Feature Guide

Manages the core kitchen schedule, dish recipes, and patient counts.

## Endpoints

### Menus & Dishes
- `GET /api/v1/menus`: List defined menu packages (e.g., Paket 1-11).
- `GET /api/v1/dishes`: List all dishes.
- `GET /api/v1/dish-compositions`: List dish-item associations (recipes).
- `POST /api/v1/dishes`: Create a new dish (Admin/Dapur).
- `PUT /api/v1/dishes/{id}`: Update a dish (Admin/Dapur).

### Scheduling & Patient Data
- `GET /api/v1/menu-schedules`: List assigned menus for dates.
- `GET /api/v1/menu-calendar`: View the current month's menu calendar.
- `GET /api/v1/daily-patients`: List record of patient counts per date.
- `POST /api/v1/daily-patients`: Update the patient count for a date.

## Business Rules

- **Slots**: Each menu (Package) is assigned to a meal time (`PAGI`, `SIANG`, `SORE`).
- **Compositions**: Dish recipes must use valid `items` and `item-units`.
- **Estimation**: SPK generation uses the `daily-patients` count as the primary multiplier for calculations.

## Related Documentation
- [Dapur Quickstart](../by-user/dapur-quickstart.md)
- [SPK Feature Guide](./spk.md)
