# Clinic Management REST API

Hệ thống quản lý phòng khám phát triển trên nền tảng Laravel + Docker Compose + PostgreSQL 16.

---

## Phiên bản Môi trường & Hệ thống

- **Docker Engine:** `29.7.0`
- **Docker Compose:** `5.3.1` (Docker Compose Plugin v2)
- **PHP Version (Container):** `8.4-cli`
- **Laravel Framework:** `13.x`
- **Database:** PostgreSQL `16`

---

## Giải thích các biến môi trường (.env)

- **`DB_CONNECTION=pgsql`**: Sử dụng hệ quản trị cơ sở dữ liệu PostgreSQL.
- **`DB_HOST=db`**: Tên host kết nối DB trong mạng Docker Compose (service `db`).
- **`DB_PORT=5432`**: Cổng kết nối PostgreSQL mặc định.
- **`DB_DATABASE=clinic_app`**: Tên cơ sở dữ liệu của ứng dụng.
- **`DB_USERNAME=clinic`**: Tên tài khoản kết nối DB.
- **`DB_PASSWORD=secret`**: Mật khẩu kết nối DB.
- **`EXAMINATION_FEE=100000`**: Phí khám bệnh mặc định (VND).
- **`PAYPAL_MODE=sandbox`**: Chế độ thử nghiệm (sandbox) của PayPal API.
- **`PAYPAL_CLIENT_ID=your-sandbox-client-id`**: Client ID tích hợp PayPal REST API (Sandbox).
- **`PAYPAL_CLIENT_SECRET=your-sandbox-client-secret`**: Client Secret tích hợp PayPal REST API (Sandbox).
- **`PAYPAL_CURRENCY=USD`**: Đơn vị tiền tệ mặc định giao dịch qua PayPal.

---

## Kiến trúc Hệ thống

Ứng dụng tuân theo mô hình **Controller + Service** nhằm đảm bảo Controller tinh gọn (Lean Controller), xử lý nghiệp vụ tập trung tại tầng Service và phân tách trách nhiệm rõ ràng.

### Luồng xử lý Request / Response

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
CONTROLLER (Điều hướng & gọi Service)
  │
  ▼
SERVICE (Xử lý nghiệp vụ chính / DB Transaction)
  │
  ▼
MODEL / ELOQUENT (Tương tác với PostgreSQL)
  │
  ▼
API RESOURCE (Format cấu trúc dữ liệu JSON)
  │
  ▼
JSON RESPONSE (Envelope: success, message, data / errors)
  │
  ▼
CLIENT
```

---


## Hướng dẫn Chạy Ứng dụng với Docker

### 1. Khởi tạo môi trường
Sao chép file cấu hình môi trường từ mẫu `.env.example`:
```bash
cp .env.example .env
```

### 2. Khởi chạy các container
Build image và khởi chạy các dịch vụ (`app` & `db` PostgreSQL 16):
```bash
docker compose up -d --build
```

### 3. Khởi tạo Key & Chạy Migration / Seed
Chạy lệnh tạo app key và khởi tạo cơ sở dữ liệu bên trong container `app`:
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 4. Truy cập Ứng dụng
Tất cả các API được phục vụ tại:
`http://localhost:8000/api`