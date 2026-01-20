# API Contract: Master Data (Department & Section)

Dokumen ini mendefinisikan kontrak API untuk endpoint master data yang dibutuhkan LMS untuk fitur reporting dan filtering.

---

## 1. Get All Divisions

### Request

```
GET /api/master/divisions
Authorization: Bearer {token}
```

### Query Parameters (Optional)

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter by name (partial match) |

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Human Capital, Finance & General Support"
    },
    {
      "id": 2,
      "name": "Operations"
    },
    {
      "id": 3,
      "name": "Technical & Engineering"
    }
  ]
}
```

---

## 2. Get All Departments

### Request

```
GET /api/master/departments
Authorization: Bearer {token}
```

### Query Parameters (Optional)

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter by name (partial match) |
| `division_id` | integer | Filter departments by division |

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Human Capital, General Service, Security & LID",
      "division": {
        "id": 1,
        "name": "Human Capital, Finance & General Support"
      }
    },
    {
      "id": 6,
      "name": "Finance & Accounting",
      "division": {
        "id": 1,
        "name": "Human Capital, Finance & General Support"
      }
    },
    {
      "id": 10,
      "name": "Production",
      "division": {
        "id": 2,
        "name": "Operations"
      }
    }
  ]
}
```

---

## 3. Get All Sections

### Request

```
GET /api/master/sections
Authorization: Bearer {token}
```

### Query Parameters (Optional)

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter by name (partial match) |
| `department_id` | integer | Filter sections by department |
| `division_id` | integer | Filter sections by division (cascading through department) |

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "LID",
      "department": {
        "id": 5,
        "name": "Human Capital, General Service, Security & LID"
      }
    },
    {
      "id": 13,
      "name": "Recruitment & Development",
      "department": {
        "id": 5,
        "name": "Human Capital, General Service, Security & LID"
      }
    },
    {
      "id": 20,
      "name": "Blending",
      "department": {
        "id": 10,
        "name": "Production"
      }
    }
  ]
}
```

---

## 4. Get All Positions

### Request

```
GET /api/master/positions
Authorization: Bearer {token}
```

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "employee"
    },
    {
      "id": 2,
      "name": "supervisor"
    },
    {
      "id": 3,
      "name": "section_head"
    },
    {
      "id": 4,
      "name": "department_head"
    },
    {
      "id": 5,
      "name": "division_head"
    },
    {
      "id": 6,
      "name": "director"
    }
  ]
}
```

### Catatan Penting:

Position `name` **HARUS** menggunakan format lowercase dengan underscore sesuai dengan yang digunakan LMS:
- `employee`
- `supervisor`
- `section_head`
- `department_head`
- `division_head`
- `director`

---

## 5. Get Users by Filter (For Dropdowns)

Endpoint ini digunakan untuk mengisi dropdown pilihan user di berbagai form LMS (misalnya: pilih peserta training).

### Request

```
GET /api/users
Authorization: Bearer {token}
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Filter by name or NRP (partial match) |
| `section` | string | Filter by exact section name |
| `department` | string | Filter by exact department name |
| `division` | string | Filter by exact division name |
| `position` | string | Filter by position (e.g., `supervisor`) |
| `limit` | integer | Limit results (default: 15, max: 100) |

### Response (Success - 200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "nrp": "12345678",
      "email": "john.doe@company.com",
      "section": "LID",
      "department": "Human Capital, General Service, Security & LID",
      "division": "Human Capital, Finance & General Support",
      "position": "section_head"
    },
    {
      "id": 124,
      "name": "Jane Smith",
      "nrp": "12345679",
      "email": "jane.smith@company.com",
      "section": "LID",
      "department": "Human Capital, General Service, Security & LID",
      "division": "Human Capital, Finance & General Support",
      "position": "employee"
    }
  ],
  "meta": {
    "total": 45,
    "returned": 15
  }
}
```

---

## 6. Data Mapping untuk LMS

### Penggunaan di LMS:

| LMS Feature | API Endpoint | Usage |
|-------------|--------------|-------|
| Reporting Filter (Department) | `GET /api/master/departments` | Dropdown filter laporan training activity |
| Reporting Filter (Section) | `GET /api/master/sections?department_id=X` | Dropdown filter section (cascade dari department) |
| Training Request Form | `GET /api/users?section=X` | Dropdown pilihan peserta training |
| Certificate Signer Detection | `GET /api/users?position=section_head&section=LID` | Mencari Section Head LID untuk tanda tangan sertifikat |

### Catatan Konsistensi:

1. **Case Sensitivity**: Nilai `section` dan `department` harus konsisten dengan yang tersimpan di database LMS (case-sensitive matching).

2. **LID Section**: Section dengan nama `LID` (uppercase) memiliki peran khusus di LMS sebagai admin training. Pastikan API mengembalikan nilai ini dengan konsisten.

3. **Human Capital Department**: Department `Human Capital, General Service, Security & LID` digunakan untuk validasi Department Head yang berhak sign sertifikat.
