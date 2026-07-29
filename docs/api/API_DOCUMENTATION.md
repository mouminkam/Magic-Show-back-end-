# API Documentation - Magic Shoe

## Base URL
```
http://localhost:8003/api
```

## Authentication
This API uses Laravel Sanctum for authentication. All protected routes require a Bearer token.

### Headers
```
Authorization: Bearer {your_token_here}
Content-Type: application/json
Accept: application/json
```

---

## Endpoints

### Health Check
```
GET /api/health
```

Response:
```json
{
  "status": "ok",
  "message": "API is running",
  "timestamp": "2025-11-05T20:00:00.000000Z"
}
```

---

## Authentication Endpoints

### Register
```
POST /api/auth/register
```

Request Body:
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response:
```json
{
  "message": "Registration successful",
  "customer": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    ...
  },
  "access_token": "1|xxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

### Login
```
POST /api/auth/login
```

Request Body:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "message": "Login successful",
  "customer": {
    "id": 1,
    "first_name": "John",
    ...
  },
  "access_token": "1|xxxxxxxxxxxx",
  "token_type": "Bearer"
}
```

### Get Authenticated User
```
GET /api/auth/user
```
**Protected Route** - Requires authentication

Response:
```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  ...
}
```

### Logout
```
POST /api/auth/logout
```
**Protected Route** - Requires authentication

Response:
```json
{
  "message": "Logged out successfully"
}
```

### Forgot Password
```
POST /api/v1/auth/forgot-password
```

Request Body:
```json
{
  "email": "john@example.com"
}
```

Response:
```json
{
  "success": true,
  "data": { "message": "We have emailed your password reset link." },
  "message": "We have emailed your password reset link."
}
```

### Reset Password
```
POST /api/v1/auth/reset-password
```

Request Body:
```json
{
  "email": "john@example.com",
  "token": "reset_token_from_email",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### Update Profile
```
PUT /api/v1/auth/profile
```
**Protected Route** - Requires authentication

Request Body (all optional):
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "address": "123 Street",
  "city": "Riyadh",
  "country": "Saudi Arabia",
  "postal_code": "12345"
}
```

---

## Newsletter Endpoints

### Subscribe
```
POST /api/v1/newsletter/subscribe
```

Request Body:
```json
{
  "email": "user@example.com"
}
```

### Unsubscribe
```
POST /api/v1/newsletter/unsubscribe
```

Request Body:
```json
{
  "email": "user@example.com"
}
```

---

## Product Endpoints

### List Products
```
GET /api/products
```

Query Parameters:
- `category_id` (optional): Filter by category ID
- `brand_id` (optional): Filter by brand ID
- `search` (optional): Search in name, description, or SKU
- `featured` (optional): Filter featured products (true/false)
- `sort_by` (optional): Sort field (name, price, created_at, updated_at) - default: created_at
- `sort_order` (optional): Sort order (asc, desc) - default: desc
- `per_page` (optional): Items per page (1-100) - default: 15
- `page` (optional): Page number

Example:
```
GET /api/products?category_id=1&featured=true&sort_by=price&sort_order=asc&per_page=20
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-name",
      "sku": "SKU123",
      "price": 99.99,
      "sale_price": 79.99,
      "currency": "SAR",
      "categories": [...],
      "brand": {...},
      "images": [...],
      ...
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75,
    ...
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Get Single Product
```
GET /api/products/{id}
```

Response:
```json
{
  "id": 1,
  "name": "Product Name",
  "slug": "product-name",
  "sku": "SKU123",
  "description": "Full description",
  "short_description": "Short description",
  "price": 99.99,
  "sale_price": 79.99,
  "currency": "SAR",
  "is_featured": true,
  "is_active": true,
  "categories": [
    {
      "id": 1,
      "name": "Category Name",
      "slug": "category-name",
      ...
    }
  ],
  "brand": {
    "id": 1,
    "name": "Brand Name",
    "slug": "brand-name",
    ...
  },
  "images": [
    {
      "id": 1,
      "url": "http://localhost:8003/storage/products/image.jpg",
      "alt_text": "Product image",
      "is_primary": true,
      "order": 0
    }
  ],
  "stock_quantity": 100,
  "in_stock": true,
  ...
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

---

## Testing

### Using cURL

#### Register
```bash
curl -X POST http://localhost:8003/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8003/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Get Products
```bash
curl http://localhost:8003/api/products
```

#### Get Authenticated User
```bash
curl http://localhost:8003/api/auth/user \
  -H "Authorization: Bearer {your_token_here}"
```

---

## Environment Setup

### .env Configuration
Add these lines to your `.env` file:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
SESSION_DOMAIN=localhost
FRONTEND_URL=http://localhost:3000
```

---

## CORS Configuration
The API is configured to accept requests from:
- `http://localhost:3000`
- `http://127.0.0.1:3000`

To add more domains, update `config/cors.php`.

---

## Notes
- All dates are returned in ISO 8601 format
- All prices are returned as floats
- Images URLs are absolute URLs
- The API uses pagination for list endpoints
- Default pagination is 15 items per page (max 100)

