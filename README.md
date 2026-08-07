# Clinic Management REST API

Clinic management system developed on Laravel + Docker Compose + PostgreSQL 16.

---

## Environment & System Versions

- **Docker Engine:** `29.7.0`
- **Docker Compose:** `5.3.1` (Docker Compose Plugin v2)
- **PHP Version (Container):** `8.4-cli`
- **Laravel Framework:** `13.x`
- **Database:** PostgreSQL `16`

---

## Environment Variables Explanation (.env)

- **`DB_CONNECTION=pgsql`**: Uses PostgreSQL database management system.
- **`DB_HOST=db`**: DB connection host name within the Docker Compose network (`db` service).
- **`DB_PORT=5432`**: Default PostgreSQL connection port.
- **`DB_DATABASE=clinic_app`**: Application database name.
- **`DB_USERNAME=clinic`**: DB connection username.
- **`DB_PASSWORD=secret`**: DB connection password.
- **`EXAMINATION_FEE=100000`**: Default examination fee (VND).
- **`PAYPAL_MODE=sandbox`**: PayPal API sandbox testing mode.
- **`PAYPAL_CLIENT_ID=your-sandbox-client-id`**: PayPal REST API Client ID (Sandbox integration).
- **`PAYPAL_CLIENT_SECRET=your-sandbox-client-secret`**: PayPal REST API Client Secret (Sandbox integration).
- **`PAYPAL_CURRENCY=USD`**: Default currency for transactions via PayPal.

---

## System Architecture

The application follows the **Controller + Service** pattern to keep Controllers lean (Lean Controller), centralize business logic within the Service layer, and ensure clear separation of concerns.

### Request / Response Flow

```text
CLIENT
  │
  ▼
ROUTE (routes/api.php)
  │
  ▼
MIDDLEWARE (auth:sanctum, EnsurePermission)
  │
  ▼
FORM REQUEST (Validation & Authorization)
  │
  ▼
CONTROLLER (Routing & Service Invocation)
  │
  ▼
SERVICE (Core Business Logic / DB Transaction)
  │
  ▼
MODEL / ELOQUENT (PostgreSQL Interaction)
  │
  ▼
API RESOURCE (Format JSON Data Structure)
  │
  ▼
JSON RESPONSE (Envelope: success, message, data / errors)
  │
  ▼
CLIENT
```

### Appointment Schedule Conflict Rule

- Each appointment occupies a fixed 30-minute time slot.
- A doctor cannot have overlapping appointments.
- Cancelled appointments are excluded from conflict checks.
- Conflict validation is applied when creating or rescheduling an appointment.
- Adjacent appointments are allowed. For example, 09:00–09:30 and 09:30–10:00 do not conflict.
- A schedule conflict returns HTTP 422 with a validation error on `scheduled_at`.

### Example Conflict
- 09:00–09:30 (Confirmed) + 09:15–09:45 (New) → Conflict
- 09:00–09:30 (Confirmed) + 09:30–10:00 (New) → No Conflict
- 09:00–09:30 (Cancelled) + 09:15–09:45 (New) → No Conflict
- 09:00–09:30 (Confirmed) + 09:45–10:15 (New) → No Conflict

---

## Guide to Running the Application with Docker

### 1. Initialize Environment
Copy the environment configuration file from `.env.example`:
```bash
cp .env.example .env
```

### 2. Start Containers
Build images and start services (`app` & `db` PostgreSQL 16):
```bash
docker compose up -d --build
```

### 3. Generate App Key & Run Migrations / Seed
Run commands to generate the application key and initialize the database inside the `app` container:
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 4. Access the Application
All APIs are served at:
`http://localhost:8000/api`