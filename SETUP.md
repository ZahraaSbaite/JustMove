# Just Move store: setup

The site runs right away in **demo mode** (sample products, no real orders):

```bash
npm install
npm run dev        # http://localhost:5173
```

To take real orders, run the Laravel + MySQL backend (folder `just-move-api`, see its README.md) and connect the site to it.

## 1. Backend

Follow `just-move-api/README.md`: it creates the MySQL tables, the starter categories (and optionally the sample products) and your staff login.

## 2. Connect the site

Copy `.env.example` to `.env`:

```
VITE_API_URL=http://localhost:8000/api
```

Restart `npm run dev`. The "Demo mode" badge disappears. For production, set `VITE_API_URL` to your live API address (for example `https://api.justmove.lb/api`) in your hosting dashboard, and add the site address to `FRONTEND_URL` in the API's `.env`.

## 3. Staff, settings and products

- Sign in at `/admin` with the account created by `php artisan admin:create`.
- `/admin/settings`: delivery fee, free-delivery threshold, your **Whish number** and **OMT name** (shown to customers at checkout).
- `/admin/products → Add product`: name, Men/Women, category, price, photos, then **Add sizes** to create the size × colour grid and enter stock for each.
- Home page photos for the Men / Women tiles and branch addresses are in `src/lib/brand.ts`.

## How orders work

| Customer pays with | What happens |
|---|---|
| Cash on delivery | Order arrives as **New**. Call to confirm, then move it along. After delivery, press **Cash collected**. |
| Whish / OMT | Customer sends the money and types the reference number. The order shows **Check transfer**. Once you see the money, press **Transfer received**. |
| Card | Shown as "Coming soon" until a card gateway is connected. |

- Stock goes down the moment an order is placed, so two people can't buy the last piece.
- **Cancel order** puts the items back in stock.
- Prices are always taken from the database, never from the customer's browser.
- The admin order list refreshes itself every 15 seconds.
- The shop gets an email for every new order (set `NOTIFY_EMAIL` and the mail settings in the API's `.env`).
