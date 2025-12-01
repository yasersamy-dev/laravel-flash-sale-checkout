 Laravel Flash Sale Checkout – GetPayIn Coding Challenge

This repository contains my implementation for the GetPayIn Laravel Coding Challenge.
The project demonstrates a concurrency-safe flash-sale checkout flow using Laravel, MySQL, and queues.
🧩 API Endpoints
➤ GET /api/products/{id}

Returns product info + real-time available stock
(Uses 1-second cache to handle flash-sale traffic)

➤ POST /api/holds

Creates a 2-minute temporary hold, immediately reducing available stock.
Protected with transactions + lockForUpdate.

➤ POST /api/orders

Creates an order from a valid, unexpired hold.
Each hold can be used once.

➤ POST /api/payments/webhook

Idempotent payment webhook:

paid → marks order as paid

failed → cancels order, restores stock, expires hold
Duplicate webhooks are safely ignored.

🔒 Concurrency & Correctness Highlights

Prevents overselling using database row locking

Accurate stock using: stock − active holds

Expired holds auto-released by a background queue job

All stock-affecting operations wrapped in DB transactions

Webhook is safe against:

🚀 Setup & Installation
git clone https://github.com/yasersamy-dev/laravel-flash-sale-checkout.git
cd laravel-flash-sale-checkout

composer install
cp .env.example .env
php artisan key:generate

DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

php artisan queue:work

php artisan serve

✔ duplicates
✔ at-least-once delivery
✔ out-of-order arrival
