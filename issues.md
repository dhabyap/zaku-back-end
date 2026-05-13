# Backend API Issues — Frontend Requirements

> **Untuk:** Junior Backend Programmer
> **Base URL:** `http://127.0.0.1:8001/api`
> **Auth:** JWT Bearer Token (header `Authorization: Bearer <token>`)
> **Response format:** `{ "status": "success", "data": { ... } }`

---

## ✅ Sudah Tersedia

- Auth: register, login, refresh, verify-email, resend-verification, forgot-password, change-password, me, logout
- User: `GET/PUT /user/profile`, `PUT /user/budget`
- Dashboard: `GET /dashboard`
- Transaksi: `GET /transactions`, `POST /transactions/chat`

---

## ❌ Belum Tersedia

---

### Transaksi Detail

| | |
|---|---|
| **Method** | `GET` |
| **Endpoint** | `/transactions/{id}` |
| **Frontend** | `dashboard/transaction-detail.blade.php:77` |
| **Auth** | Yes (JWT) |

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "type": "expense" | "income",
    "amount": 35000,
    "description": "Beli makan siang",
    "category": "MAKANAN",
    "created_at": "2025-01-15T12:00:00Z"
  }
}
```

---

### Transaction Stats

| | |
|---|---|
| **Method** | `GET` |
| **Endpoint** | `/transactions/stats` |
| **Frontend** | `dashboard/profile.blade.php:144` |
| **Auth** | Yes (JWT) |

**Response:**
```json
{
  "status": "success",
  "data": {
    "total": 47,
    "this_month": 12,
    "biggest": 7500000,
    "categories": 8
  }
}
```

---

### Transaction Categories

| | |
|---|---|
| **Method** | `GET` |
| **Endpoint** | `/transactions/categories` |
| **Frontend** | `dashboard/home.blade.php:177` |
| **Auth** | Yes (JWT) |

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "name": "MAKANAN",
      "amount": 1500000,
      "pct": 35
    }
  ]
}
```

---

### Wallet Balance

| | |
|---|---|
| **Method** | `GET` |
| **Endpoint** | `/wallet/balance` |
| **Frontend** | `dashboard/home.blade.php:154`, `wallet/*.blade.php` |
| **Auth** | Yes (JWT) |

**Response:**
```json
{
  "status": "success",
  "data": {
    "balance": 3250000,
    "total_income": 7500000,
    "total_expense": 4250000
  }
}
```

---

### Top Up

| | |
|---|---|
| **Method** | `POST` |
| **Endpoint** | `/wallet/topup` |
| **Frontend** | `wallet/topup.blade.php:62` |
| **Auth** | Yes (JWT) |

**Request:**
```json
{ "amount": 100000 }
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "balance": 3350000,
    "message": "Top up berhasil."
  }
}
```

---

### Withdraw

| | |
|---|---|
| **Method** | `POST` |
| **Endpoint** | `/wallet/withdraw` |
| **Frontend** | `wallet/withdraw.blade.php:59` |
| **Auth** | Yes (JWT) |

**Request:**
```json
{ "amount": 200000, "account_number": "1234567890" }
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "balance": 3050000,
    "message": "Penarikan berhasil diproses."
  }
}
```

---

### Send Money

| | |
|---|---|
| **Method** | `POST` |
| **Endpoint** | `/wallet/send` |
| **Frontend** | `wallet/send-money.blade.php:65` |
| **Auth** | Yes (JWT) |

**Request:**
```json
{
  "recipient_email": "user@email.com",
  "amount": 50000,
  "note": "opsional"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "balance": 3000000,
    "message": "Uang berhasil dikirim."
  }
}
```
