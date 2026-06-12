# Vendor Machine

Laravel middleware between a vending machine and Cellulant (Tingg) Mobile Money.

## Flow

1. Customer selects coffee and enters phone on the **machine**
2. Machine calls `POST /api/vending/create-order` with order + phone
3. Middleware initiates Cellulant payment and returns `{ status: PENDING, transactionId }`
4. Customer approves MTN/Airtel prompt on their phone
5. Cellulant sends IPN to `/api/cellulant/ipn`
6. Machine polls `POST /api/vending/payment-status` until `{ paid: true }`
7. Machine dispenses and reports `POST /api/vending/dispense-result`

## Machine endpoints

| Machine field | Laravel URL |
|---------------|-------------|
| Create order | `{APP_URL}/api/vending/create-order` |
| Payment status | `{APP_URL}/api/vending/payment-status` |
| Dispense result | `{APP_URL}/api/vending/dispense-result` |

## Cellulant admin

Configure sandbox/production credentials at **`/settings/cellulant`** after login.

Register the IPN URL shown on that page in the Cellulant/Tingg portal.

Default sandbox credentials are seeded (counter `1008`).

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Admin: `admin@vendormachine.test` / `password`

## Frontend assets (Vite)

CSS and JS are built with **Vite**. If you only run `php artisan serve`, build assets first:

```bash
npm run build
```

For hot reload during development, run both together:

```bash
composer dev
```

If styles suddenly disappear, a stale Vite dev marker is usually the cause:

```bash
rm -f public/hot
npm run build
```
