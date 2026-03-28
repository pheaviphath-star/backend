# api-assignment-vue (Laravel API)

Backend API for the Hotel / Reservation project.

## Requirements

- PHP >= 8.0.2
- Composer
- MySQL / MariaDB

## Setup

1) Install dependencies

```bash
composer install
```

2) Create env file

```bash
copy .env.example .env
```

3) Configure `.env`

Update your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

Cloudinary (required for image upload):

```env
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

4) Generate app key

```bash
php artisan key:generate
```

5) JWT secret (if not generated yet)

This project uses `tymon/jwt-auth` with the `api` guard.

```bash
php artisan jwt:secret
```

6) Run migrations

```bash
php artisan migrate
```

7) Start the server

```bash
php artisan serve
```

Default URL:

- `http://127.0.0.1:8000`

## Authentication

- Public endpoints are under: `/api/public/*`
- Protected endpoints require `Authorization: Bearer <token>`

## Main API Routes

Base prefix: `/api`

### Auth

- `POST /register`
- `POST /login`
- `POST /logout`

### Public

- `GET /public/rooms`
- `POST /public/guests`
- `POST /public/guests/login`
- `GET /public/reservations/history`
- `POST /public/reservations`
- `POST /public/reservations/{id}/confirm-payment`
- `POST /public/reservations/{id}/cancel`

Bakong (KHQR payment):

- `POST /public/bakong/khqr`
- `POST /public/bakong/verify`
- `POST /public/bakong/verify-transaction`

### Protected (JWT)

- `GET /user`
- `PUT /profile`
- `POST /profile/image`
- `DELETE /profile/image`
- `PUT /profile/password`

Resources:

- `apiResource /role`
- `apiResource /permissions`
- `apiResource /users`
- `apiResource /rooms`
- `apiResource /guests`
- `apiResource /reservations`
- `apiResource /historys`

Role/Permission assignment:

- `GET /permission_roles`
- `POST /permission_roles`
- `PUT /permission_roles/{roleId}/{permissionId}`
- `DELETE /permission_roles/{roleId}/{permissionId}`

- `GET /users_roles`
- `POST /users_roles`
- `PUT /users_roles/{userId}/{roleId}`
- `DELETE /users_roles/{userId}/{roleId}`

User profile image by user id:

- `POST /users/{id}/profile/image`
- `DELETE /users/{id}/profile/image`

Reports:

- `GET /reports`

## Notes

- Image uploads are stored on Cloudinary. The API stores the `secure_url` in the DB.
- Do not commit real secrets to git. Keep Cloudinary keys and database credentials in `.env`.
