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

## PayPal Integration & Security Guidelines

> [!WARNING]
> **SANDBOX MODE ONLY - DO NOT USE REAL MONEY OR PRODUCTION CREDENTIALS**
> This application is configured exclusively for PayPal Sandbox development. Never commit production API keys or real credit card information to the repository.

### Security Guidelines (Credential Protection)

- All PayPal API credentials (`PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`) must be stored strictly inside your local `.env` file.
- Do not commit your `.env` file to version control. The `.gitignore` file is configured to exclude `.env`.
- The `.env.example` template provides placeholder values for developer setup.

---

### Step-by-Step PayPal Sandbox Setup

#### 1. Create a PayPal Developer App

1. Go to the [PayPal Developer Dashboard](https://developer.paypal.com/).
2. Log in or create a developer account.
3. In the sidebar under **Sandbox**, navigate to **Apps & Credentials**.
4. Click **Create App**.
5. Set the app type to **Merchant** or **Platform**, enter an app name, and select your sandbox business account.
6. Copy the generated **Client ID** and **Secret** into your local `.env` file:
   ```env
   PAYPAL_MODE=sandbox
   PAYPAL_CLIENT_ID=your-sandbox-client-id
   PAYPAL_CLIENT_SECRET=your-sandbox-client-secret
   PAYPAL_CURRENCY=USD
   ```

#### 2. Create Sandbox Accounts & Visa Test Cards

1. In the PayPal Developer Dashboard sidebar, go to **Testing Tools > Sandbox Accounts**.
2. Click **Create Account** to generate a Personal (Buyer) account or use the default sandbox buyer account.
3. To test Visa card payments (`method = "visa"`):
   - Open the returned `approval_url` on the PayPal hosted checkout page and select **Pay with Debit or Credit Card** (Visa).
   - Alternatively, generate test card numbers under **Testing Tools > Credit Card Generator** in the PayPal Developer Dashboard.
   - **Do NOT use real credit card numbers under any circumstances.**

---

### Payment API Workflow (PayPal & Visa)

1. **Create Payment (`POST /api/invoices/{invoice}/payments`)**:
   - Accepts `method = "paypal"` or `method = "visa"`.
   - Generates a PayPal Order and returns an `approval_url` along with `order_id` and a `pending` payment record.
2. **Approve Payment (Browser Authorization)**:
   - Copy the returned `approval_url` and paste it into a web browser.
   - Log in using any **PayPal Sandbox Personal (Buyer) account** *(do NOT use the developer/merchant account that created the app)*.
   - Choose your preferred payment method (PayPal balance or Debit/Credit Card / Visa) and click **Pay Now** / **Approve**.
   - > [!NOTE]
   - > **PayPal Sandbox UI Behavior:** The PayPal Sandbox Web UI may display browser console warnings (such as CORS or CSP telemetry errors) or fail to redirect automatically after approval. This is an expected behavior of the PayPal Sandbox web interface and does not impact backend API processing. Once you click approve on PayPal, proceed directly to Postman to execute the Capture request.
3. **Capture Payment (`POST /api/payments/{payment}/capture`)**:
   - Captures the authorized PayPal transaction, marks the payment as `completed`, records `provider_capture_id`, and automatically updates the Invoice status to `paid` once the remaining total is settled.