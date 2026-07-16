# Vendor Machine

Laravel middleware between a vending machine and Cellulant (Tingg) Mobile Money.

## Overview

This app sits between the vending machine and Cellulant:

1. Customer selects a product on the machine (item, price, order ID).
2. Machine calls `POST /api/vending/create-order` (no phone required).
3. Middleware creates the order and returns a payment URL (`twocode`) for the QR / pay page.
4. Customer opens the pay page, enters their Mobile Money number, and approves the prompt.
5. Cellulant sends IPN to `/api/cellulant/ipn`.
6. Machine polls `POST /api/vending/payment-status` until payment is confirmed.
7. Machine dispenses the item and reports back to `POST /api/vending/dispense-result`.

If the machine also sends a phone number on create-order, payment is started immediately and the QR step is skipped.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ or compatible MariaDB
- Node.js 18+ and npm

## Local Installation

1. Clone the project and enter the folder:

```bash
git clone <your-repository-url>
cd VendorMachine
```

2. Install backend dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Create the environment file:

```bash
cp .env.example .env
```

5. Generate the Laravel application key:

```bash
php artisan key:generate
```

6. Create a database and update the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vendor_machine
DB_USERNAME=root
DB_PASSWORD=
```

7. Run migrations and seed default data:

```bash
php artisan migrate --seed
```

8. Build frontend assets:

```bash
npm run build
```

9. Start the app:

```bash
php artisan serve
```

For local hot reload, use:

```bash
composer dev
```

Admin login:

```text
Email: admin@vendormachine.test
Password: password
```

## Environment Setup

Set these core values in `.env`:

```env
APP_NAME="La Patisserie Express"
APP_ENV=local
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vendor_machine
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

VENDING_VERIFY_SIGNATURE=true
VENDING_DEFAULT_CHANNEL_ID=36
VENDING_ORDER_EXPIRY_MINUTES=15
VENDING_SYNC_PENDING_PAYMENTS=true
VENDING_SYNC_PENDING_BATCH_SIZE=50
VENDING_SYNC_PENDING_SCHEDULE=everyMinute
```

Cellulant credentials are managed inside the app, not in `.env`.

After login, configure them at:

- `/settings/cellulant`

## Cellulant Setup

After logging into the app:

1. Open **Cellulant Settings** at `/settings/cellulant`.
2. Choose the environment: sandbox or production.
3. Enter the API username and API key.
4. Enter the counter code.
5. Save settings.
6. Use the test tools on the page to confirm OAuth and payment initiation work.

Default sandbox counter:

- `1008`

Important headers already handled by the app for Cellulant:

- `X-Country-Code: UGA`
- `Currency-Code: UGX`
- `Request-Origin: TINGG_INSTORE_INTEGRATION`

## Machine Setup

Configure each vending machine in the admin panel:

1. Add the machine ID (`machid`).
2. Add the machine secret key (`appkey` equivalent).
3. Make sure the machine status is active.

The machine signs requests using:

1. `secret_key`
2. `timestamp`
3. `randstr`

Sort those three values alphabetically, concatenate them, then generate:

```text
sign = SHA1(concatenated_value)
```

## Machine Endpoints

Configure these URLs on the vending machine:

| Machine field | Laravel URL |
|---------------|-------------|
| Create order | `{APP_URL}/api/vending/create-order` |
| Payment status | `{APP_URL}/api/vending/payment-status` |
| Dispense result | `{APP_URL}/api/vending/dispense-result` |

Create-order only needs order ID, machine ID, product name, and price. Phone is optional.

When the machine sends `ver=v1` (LE Machine protocol), the response includes `twocode` pointing to `{APP_URL}/pay/{torderid}` where the customer enters their phone number.

The LE protocol sends `price` as the configured UGX price multiplied by 100. The
middleware divides `price` by `VENDING_MACHINE_PRICE_DIVISOR` (default `100`)
before storing it or sending it to Cellulant. The modern `amount` field is
already treated as UGX and is not divided.

Supported request field aliases:

| Meaning | Accepted fields |
|---------|-----------------|
| Order ID | `orderId`, `orderid`, `transactionId` |
| Machine ID | `machineId`, `machid` |
| Product name | `product`, `name` |
| Amount | `amount`, `price` |
| Phone number (optional) | `phoneNumber`, `phone_number`, `msisdn` |

## IPN Setup

Cellulant must be able to reach your public app URL.

Register this IPN URL in the Tingg portal:

```text
{APP_URL}/api/cellulant/ipn
```

Do not use `localhost` for production `APP_URL`.

## Production Deployment

Typical deployment steps:

1. Upload the project to the server.
2. Point the web root to Laravel's `public` directory.
3. Copy `.env.example` to `.env` and update production values.
4. Install dependencies:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

5. Run database migrations:

```bash
php artisan migrate --force
```

6. Cache config, routes, and views:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. Make sure `storage` and `bootstrap/cache` are writable.

Recommended production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
VENDING_VERIFY_SIGNATURE=true
```

## Scheduler and Cron

Pending payments are synced with Cellulant as a backup when IPN or machine polling is delayed.

Manual command:

```bash
php artisan payments:sync-pending
```

Laravel scheduler entry:

```cron
* * * * * cd /path/to/VendorMachine && php artisan schedule:run >> /dev/null 2>&1
```

On Hostinger-like setups, this is usually one of:

```bash
cd /home/USERNAME/domains/coffee.quisat.com && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

or

```bash
/usr/bin/php /home/USERNAME/domains/coffee.quisat.com/artisan schedule:run >> /dev/null 2>&1
```

Tune scheduler behavior with:

- `VENDING_SYNC_PENDING_PAYMENTS`
- `VENDING_SYNC_PENDING_BATCH_SIZE`
- `VENDING_SYNC_PENDING_SCHEDULE`

Allowed schedule values:

- `everyMinute`
- `everyFiveMinutes`
- `everyTenMinutes`

## Logging and Monitoring

The app includes:

- IPN logs
- Integration/API logs
- Order and payment tracking

Useful admin pages:

- `/ipn-logs`
- `/integration-logs`
- `/orders`
- `/machines`
- `/settings/cellulant`

## Frontend Assets

CSS and JS are built with Vite. If styles disappear, rebuild assets:

```bash
rm -f public/hot
npm run build
```

## Quick Go-Live Checklist

- Set correct production `APP_URL`
- Run `php artisan migrate --force`
- Configure Cellulant production credentials
- Register production IPN URL in Tingg
- Configure machine IDs and secret keys
- Set machine API endpoints
- Add Laravel cron entry for `schedule:run`
- Test one full payment from machine to dispense
