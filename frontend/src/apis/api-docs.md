# API Documentation

This document describes the structure, parameters, and return types for the APIs used by the frontend.

## General Response Format

All API endpoints return a standard JSON object wrapper:

```typescript
type ApiResponse<T> = {
  success: boolean;   // Indicates if the request was successful
  message: string;    // Human-readable message (e.g. error message or success confirmation)
  data?: T;           // The actual payload data (can be missing if success is false)
};
```

---

## 1. Products APIs

### `GET /api/products`
Retrieves a paginated list of products.

- **Query Parameters**:
  - `q` (string, optional): Keyword to search products by name, SKU, or brand.
  - `page` (number, optional): The current page number. Defaults to 1.
  - `limit` (number, optional): Items per page. Defaults to 10.

- **Returns**: `ApiResponse<ProductListResponse>`
```typescript
type ProductListResponse = {
  items: ProductVersion[];
  pagination: {
    current_page: number;
    total_pages: number;
    total_items: number;
    limit: number;
  };
};
```

### `GET /api/products/detail?id={id}`
Retrieves details for a specific product version.

- **Query Parameters**:
  - `id` (number, required): The ID of the product version.

- **Returns**: `ApiResponse<ProductVersion>`

### `POST /api/products`
Creates a new product version.

- **Body Parameters (JSON)**:
  - `product_name` (string, required)
  - `sku` (string, required)
  - `version_name` (string, required)
  - `price` (number, required)
  - `stock_quantity` (number, required)
  - `format_type` (string, optional)
  - `language` (string, optional)
  - `description` (string, optional)

- **Returns**: `ApiResponse<ProductVersion>`

---

## 2. Authentication APIs

### `POST /api/customer/login`
Authenticates a customer.

- **Body Parameters (JSON)**:
  - `email` (string, required)
  - `password` (string, required)

- **Returns**: `ApiResponse<User>`
```typescript
type User = {
  user_id: number;
  email: string;
  name: string;
  phone?: string;
  // ... other user details
};
```

### `POST /api/register`
Registers a new customer account.

- **Body Parameters (JSON)**:
  - `email` (string, required)
  - `password` (string, required)
  - `name` (string, required)

- **Returns**: `ApiResponse<User>`

---

## 3. Cart APIs

### `GET /api/cart`
Retrieves the active cart for the user.

- **Returns**: `ApiResponse<CartItem[]>`

### `POST /api/cart/add`
Adds a product version to the cart.

- **Body Parameters (JSON)**:
  - `customer_id` (number, required)
  - `version_id` (number, required)
  - `quantity` (number, required)

- **Returns**: `ApiResponse<CartItem>`
