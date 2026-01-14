# API Contract: Authentication & User Profile

Dokumen ini mendefinisikan kontrak API untuk endpoint autentikasi dan pengambilan profil user yang akan diintegrasikan dengan LMS (Learning Center).

---

## 1. Login Endpoint

### Request

```
POST /api/auth/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "john.doe@company.com",
  "password": "secret123"
}
```

### Response (Success - 200 OK)

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 123,
      "email": "john.doe@company.com",
      "profile": {
        "nik": "12345678",
        "name": "John Doe",
        "avatar": "https://api.example.com/storage/avatars/john.jpg",
        "gender": "male"
      },
      "organization": {
        "division": {
          "id": 1,
          "name": "Human Capital, Finance & General Support"
        },
        "department": {
          "id": 5,
          "name": "Human Capital, General Service, Security & LID"
        },
        "section": {
          "id": 12,
          "name": "LID"
        },
        "position": {
          "id": 3,
          "name": "section_head"
        }
      }
    }
  }
}
```

### Response (Failed - 401 Unauthorized)

```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### Response (Failed - 422 Validation Error)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## 2. Get Current User Profile

Endpoint untuk mengambil data user yang sedang login (refresh data).

### Request

```
GET /api/auth/me
Authorization: Bearer {token}
```

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": {
    "id": 123,
    "email": "john.doe@company.com",
    "profile": {
      "nik": "12345678",
      "name": "John Doe",
      "avatar": "https://api.example.com/storage/avatars/john.jpg",
      "gender": "male"
    },
    "organization": {
      "division": {
        "id": 1,
        "name": "Human Capital, Finance & General Support"
      },
      "department": {
        "id": 5,
        "name": "Human Capital, General Service, Security & LID"
      },
      "section": {
        "id": 12,
        "name": "LID"
      },
      "position": {
        "id": 3,
        "name": "section_head"
      }
    }
  }
}
```

### Response (Failed - 401 Unauthenticated)

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## 3. Logout Endpoint

### Request

```
POST /api/auth/logout
Authorization: Bearer {token}
```

### Response (Success - 200 OK)

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 4. Data Mapping untuk LMS

Berikut adalah pemetaan field dari API response ke tabel `users` lokal di LMS:

| API Response Path                | LMS `users` Column | Keterangan |
|----------------------------------|-------------------|------------|
| `data.user.id`                   | `id` (atau `external_id`) | Primary identifier dari API |
| `data.user.email`                | `email` | Unique identifier untuk login |
| `data.user.profile.nik`          | `nrp` | Nomor identifikasi karyawan |
| `data.user.profile.name`         | `name` | Nama lengkap |
| `data.user.organization.division.name` | `division` | Nama divisi (string) |
| `data.user.organization.department.name` | `department` | Nama departemen (string) |
| `data.user.organization.section.name` | `section` | Nama seksi (string) |
| `data.user.organization.position.name` | `position` | Jabatan struktural |

### Catatan Penting:

1. **Position Values**: LMS mengharapkan nilai `position` berupa salah satu dari:
   - `employee`
   - `supervisor`
   - `section_head`
   - `department_head`
   - `division_head`
   - `director`
   
   API harus mengembalikan nilai position dalam format tersebut (lowercase dengan underscore).

2. **Hardcoded Logic di LMS**: 
   Beberapa fitur LMS memiliki logic hardcoded yang bergantung pada nilai exact string:
   - Section `LID` (uppercase) digunakan untuk menentukan akses admin training.
   - Department `Human Capital, General Service, Security & LID` digunakan untuk approval sertifikat.
   
   Pastikan nilai ini konsisten dari API.

3. **Password**: Field password di tabel lokal LMS akan diset `NULL` karena autentikasi dilakukan via API. Password tidak boleh dikembalikan oleh API.

---

## 5. Error Codes

| HTTP Status | Meaning |
|-------------|---------|
| 200 | Success |
| 401 | Unauthorized - Invalid credentials or expired token |
| 403 | Forbidden - User doesn't have permission |
| 422 | Validation Error - Missing or invalid fields |
| 500 | Server Error |
