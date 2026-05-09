# 📚 BookStore — E-commerce Web Application

> Web Programming Course Project @ HCMUT

A full-stack e-commerce bookstore built with PHP (custom MVC), MySQL, React + TypeScript (customer frontend), and SrtDash (admin dashboard).

---

## 🏗️ Architecture

```
BTL/
├── backend/          # PHP REST API (custom MVC, no framework)
│   ├── app/
│   │   ├── Controllers/    # Request handlers
│   │   ├── Models/         # Database queries
│   │   ├── Views/          # (unused — API-only)
│   │   ├── helpers/        # Response helper
│   │   └── services/       # Auth service
│   ├── config/             # DB configuration
│   └── public/             # Entry point, uploads, Swagger
├── frontend/         # React + Vite (customer SPA)
├── admin/            # SrtDash template (admin dashboard)
└── db.sql            # Full database schema + seed data
```

## 🚀 Quick Start

### Prerequisites
- PHP 7.0+
- MySQL 5.7+
- Node.js 16+ (for frontend)

### Setup

```bash
# 1. Create MySQL database
mysql -u root -p -e "CREATE DATABASE web_btl;"

# 2. Import schema + seed data
mysql -u root -p web_btl < db.sql

# 3. Configure database connection
# Edit backend/config/database.local.php with your credentials

# 4. Install frontend dependencies
cd frontend && npm install

# 5. Start everything
# From project root:
start.bat
```

### Manual Start
```bash
# Backend API (port 8000)
cd backend/public && php -S localhost:8000 index.php

# Frontend (port 5173)
cd frontend && npm run dev

# Admin (port 5500)
cd admin && php -S localhost:5500
```

### Test Accounts
| Role | Email | Password |
|------|-------|----------|
| Admin (super) | admin1@store.com | admin123 |
| Admin | admin2@store.com | admin123 |
| Customer | customer1@gmail.com | customer123 |

---

## 📡 API Documentation

Base URL: `http://localhost:8000/api`

Interactive Swagger UI: `http://localhost:8000/docs`

### Auth

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/customer/login` | Customer login | ❌ |
| `POST` | `/api/admin/login` | Admin login | ❌ |
| `POST` | `/api/register` | Customer registration | ❌ |
| `GET` | `/api/auth/check-status?user_id=X` | Check if customer is banned | ❌ |

<details>
<summary>📋 Request/Response Examples</summary>

**POST /api/customer/login**
```json
// Request
{ "identifier": "customer1@gmail.com", "password": "customer123" }

// Response 200
{
  "success": true,
  "message": "Đăng nhập customer thành công",
  "data": {
    "user_id": 4, "full_name": "Le Minh Customer",
    "email": "customer1@gmail.com", "phone": "0912000001",
    "role": "customer", "customer_status": 1,
    "shipping_address": "...", "receiver_name": "..."
  }
}
```

**POST /api/register**
```json
// Request
{
  "full_name": "Test User", "email": "test@gmail.com",
  "password": "123456", "phone": "0912345678",
  "shipping_address": "123 Main St",
  "receiver_name": "Test User", "receiver_phone": "0912345678"
}
```
</details>

---

### Customer Profile

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/customer/info?user_id=X` | Get customer profile | 🔒 Customer |
| `PUT` | `/api/customer/update` | Update customer info | 🔒 Customer |
| `PUT` | `/api/customer/change-password` | Change password | 🔒 Customer |
| `POST` | `/api/customer/upload-avatar` | Upload avatar (multipart) | 🔒 Customer |

<details>
<summary>📋 Request Examples</summary>

**PUT /api/customer/update**
```json
{
  "user_id": 4, "full_name": "New Name",
  "phone": "0912345678", "shipping_address": "456 New St",
  "receiver_name": "Receiver", "receiver_phone": "0912345678"
}
```

**PUT /api/customer/change-password**
```json
{
  "user_id": 4, "old_password": "customer123",
  "new_password": "newpass123", "confirm_password": "newpass123"
}
```

**POST /api/customer/upload-avatar** (multipart/form-data)
- `user_id`: integer
- `avatar`: file (jpg/png/webp, max 2MB)
</details>

---

### Admin Profile

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/admin/info?admin_id=X` | Get admin profile | 🔒 Admin |
| `PUT` | `/api/admin/update` | Update admin info | 🔒 Admin |
| `PUT` | `/api/admin/change-password` | Change admin password | 🔒 Admin |

---

### Admin: Customer Management

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/admin/customers?q=&page=1&limit=10` | List customers | 🔒 Admin |
| `PUT` | `/api/admin/customers/status?id=X` | Ban/unban customer | 🔒 Super Admin |
| `PUT` | `/api/admin/customers/reset-password` | Reset password to `123456` | 🔒 Super Admin |

<details>
<summary>📋 Request Examples</summary>

**PUT /api/admin/customers/status?id=4**
```json
{ "status": 0, "admin_id": 1 }
```

**PUT /api/admin/customers/reset-password**
```json
{ "customer_id": 4, "admin_id": 1 }
```
</details>

---

### Products

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/products?q=&page=1&limit=10` | List products | ❌ |
| `GET` | `/api/products/detail?id=X` | Product detail | ❌ |
| `POST` | `/api/products` | Create product | 🔒 Admin |
| `PUT` | `/api/products/update?id=X` | Update product | 🔒 Admin |
| `DELETE` | `/api/products/delete?id=X` | Delete product version | 🔒 Admin |
| `POST` | `/api/products/upload-image` | Upload product image | 🔒 Admin |

<details>
<summary>📋 Request Examples</summary>

**POST /api/products**
```json
{
  "product_name": "New Book", "brand": "Publisher",
  "description": "A great book", "sku": "BK-001",
  "version_name": "Paperback - EN", "format_type": "paperback",
  "language": "English", "cover_type": "Soft Cover",
  "edition": "1st Edition", "price": 150000,
  "stock_quantity": 50, "version_status": "available"
}
```
</details>

---

### Categories

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/categories` | List all categories (flat) | ❌ |
| `GET` | `/api/categories/tree` | Category tree (nested) | ❌ |
| `GET` | `/api/categories/products?id=X&page=1&limit=12` | Products by category | ❌ |
| `POST` | `/api/admin/categories` | Create category | 🔒 Admin |
| `PUT` | `/api/admin/categories/update?id=X` | Update category | 🔒 Admin |
| `DELETE` | `/api/admin/categories/delete?id=X` | Delete category | 🔒 Admin |

---

### Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/orders?q=&page=1&limit=10` | Admin: list all orders | 🔒 Admin |
| `GET` | `/api/orders/detail?id=X` | Order detail | 🔒 |
| `PUT` | `/api/orders/move-to-shipping?id=X` | Admin: confirmed → shipping | 🔒 Admin |
| `PUT` | `/api/orders/customer-confirm-payment?id=X` | Customer: confirm payment | 🔒 Customer |
| `PUT` | `/api/orders/customer-cancel?id=X` | Customer: cancel pending order | 🔒 Customer |
| `PUT` | `/api/orders/customer-confirm-delivered?id=X` | Customer: confirm delivery | 🔒 Customer |
| `POST` | `/api/orders/create-from-cart` | Create order from cart | 🔒 Customer |
| `GET` | `/api/orders/customer?customer_id=X` | Customer's orders | 🔒 Customer |

<details>
<summary>📋 Order Flow</summary>

```
Customer creates order → pending
Customer confirms payment → confirmed (stock deducted)
Admin moves to shipping → shipping
Customer confirms delivery → delivered

Customer can cancel → cancelled (only when pending)
```
</details>

---

### Cart

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/cart?customer_id=X` | List cart items | 🔒 Customer |
| `POST` | `/api/cart/add` | Add to cart | 🔒 Customer |
| `PUT` | `/api/cart/update` | Update quantity | 🔒 Customer |
| `DELETE` | `/api/cart/remove` | Remove item | 🔒 Customer |
| `DELETE` | `/api/cart/clear` | Clear entire cart | 🔒 Customer |

<details>
<summary>📋 Request Examples</summary>

**POST /api/cart/add**
```json
{ "customer_id": 4, "version_id": 1, "quantity": 2 }
```

**PUT /api/cart/update**
```json
{ "customer_id": 4, "cart_item_id": 1, "quantity": 3 }
```

**DELETE /api/cart/remove** (JSON body)
```json
{ "customer_id": 4, "cart_item_id": 1 }
```
</details>

---

### Contacts

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/contact` | Guest: submit contact form | ❌ |
| `GET` | `/api/admin/contacts?q=&status=&page=1&limit=10` | Admin: list contacts | 🔒 Admin |
| `GET` | `/api/admin/contacts/detail?id=X` | Admin: contact detail | 🔒 Admin |
| `PUT` | `/api/admin/contacts/status?id=X` | Admin: update status | 🔒 Admin |
| `DELETE` | `/api/admin/contacts/delete?id=X` | Admin: delete contact | 🔒 Admin |

<details>
<summary>📋 Contact Statuses</summary>

`new` → `in_progress` → `replied` → `closed`
</details>

---

### FAQs

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/faqs?category=X` | Public: active FAQs | ❌ |
| `GET` | `/api/faqs/categories` | Public: FAQ categories | ❌ |
| `GET` | `/api/admin/faqs?q=&page=1&limit=10` | Admin: all FAQs | 🔒 Admin |
| `POST` | `/api/admin/faqs` | Admin: create FAQ | 🔒 Admin |
| `GET` | `/api/admin/faqs/detail?id=X` | Admin: FAQ detail | 🔒 Admin |
| `PUT` | `/api/admin/faqs/update?id=X` | Admin: update FAQ | 🔒 Admin |
| `DELETE` | `/api/admin/faqs/delete?id=X` | Admin: delete FAQ | 🔒 Admin |

---

### Site Settings

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/settings?group=X` | Public: get settings | ❌ |
| `GET` | `/api/admin/settings` | Admin: all settings (detailed) | 🔒 Admin |
| `GET` | `/api/admin/settings/grouped` | Admin: grouped settings | 🔒 Admin |
| `PUT` | `/api/admin/settings/update` | Admin: bulk update | 🔒 Admin |

<details>
<summary>📋 Request Example</summary>

**PUT /api/admin/settings/update**
```json
{
  "admin_id": 1,
  "settings": {
    "site_name": "My New Store",
    "contact_email": "new@store.com",
    "contact_phone": "0901234567"
  }
}
```
</details>

---

### Product Sections

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/sections` | Public: active sections | ❌ |
| `GET` | `/api/admin/sections` | Admin: all sections | 🔒 Admin |
| `POST` | `/api/admin/sections` | Admin: create (multipart) | 🔒 Admin |
| `GET` | `/api/admin/sections/detail?id=X` | Admin: section detail | 🔒 Admin |
| `POST` | `/api/admin/sections/update?id=X` | Admin: update (multipart) | 🔒 Admin |
| `DELETE` | `/api/admin/sections/delete?id=X` | Admin: delete section | 🔒 Admin |

---

### Flash Sales

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/flash-sales` | Public: active sales + products | ❌ |
| `GET` | `/api/admin/flash-sales` | Admin: all flash sales | 🔒 Admin |
| `POST` | `/api/admin/flash-sales` | Admin: create (multipart) | 🔒 Admin |
| `GET` | `/api/admin/flash-sales/detail?id=X` | Admin: detail + products | 🔒 Admin |
| `POST` | `/api/admin/flash-sales/update?id=X` | Admin: update (multipart) | 🔒 Admin |
| `DELETE` | `/api/admin/flash-sales/delete?id=X` | Admin: delete | 🔒 Admin |

---

## 🗄️ Database Schema

| Table | Description |
|-------|-------------|
| `users` | All users (admin + customer) |
| `admins` | Admin-specific data (salary, super_admin) |
| `customers` | Customer-specific data (address, status) |
| `categories` | Product categories (parent-child) |
| `products` | Base product info |
| `product_versions` | Product variants (format, price, stock) |
| `product_categories` | M:N product ↔ category |
| `carts` | One cart per customer |
| `cart_items` | Items in cart |
| `orders` | Order header |
| `order_items` | Order line items (snapshots) |
| `payments` | Payment info per order |
| `order_status_history` | Order state transitions |
| `contacts` | Guest contact form submissions |
| `faqs` | FAQ entries |
| `site_settings` | Key-value site configuration |
| `flash_sales` | Flash sale campaigns |
| `flash_sale_products` | Products in flash sales |
| `product_section` | Curated product sections |
| `product_section_details` | Products in sections |

---

