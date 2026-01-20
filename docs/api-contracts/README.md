# API Contract Summary: External User Management API

Dokumen ini merupakan ringkasan dari seluruh API Contract yang dibutuhkan untuk integrasi LMS (Learning Center) dengan External User Management API.

---

## Quick Reference

| Contract File | Endpoints | Purpose |
|---------------|-----------|---------|
| [auth-contract.md](./auth-contract.md) | `POST /auth/login`, `GET /auth/me`, `POST /auth/logout` | Autentikasi user |
| [master-data-contract.md](./master-data-contract.md) | `GET /master/divisions`, `GET /master/departments`, `GET /master/sections`, `GET /master/positions`, `GET /users` | Data referensi & filtering |
| [webhook-contract.md](./webhook-contract.md) | `POST /webhooks/user-sync` | Real-time synchronization |

---

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          INTEGRATION FLOW                                │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────┐              ┌─────────────────────────────────┐   │
│  │   API UTAMA     │              │           LMS                   │   │
│  │   (Master)      │              │    (Learning Center)            │   │
│  │                 │              │                                 │   │
│  │  /auth/login ──────────────────►  SyncUserService               │   │
│  │  /auth/me    ──────────────────►  - Create/Update local user    │   │
│  │                 │              │  - Set role (if new user)      │   │
│  │                 │              │                                 │   │
│  │  /master/*   ──────────────────►  Dropdown filters              │   │
│  │                 │              │  - Reports                      │   │
│  │                 │              │  - Forms                        │   │
│  │                 │              │                                 │   │
│  │  Webhook ───────────────────────►  Real-time sync               │   │
│  │  user.updated   │              │  - Update profile changes      │   │
│  │  user.created   │              │  - Handle new employees        │   │
│  │  user.deleted   │              │  - Soft-delete resigned        │   │
│  │                 │              │                                 │   │
│  └─────────────────┘              └─────────────────────────────────┘   │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Critical Data Requirements

### 1. Position Values (MUST be exact)

LMS memiliki logic approval dan akses yang bergantung pada nilai `position`. API **HARUS** mengembalikan salah satu dari nilai berikut:

| Position Value | LMS Usage |
|----------------|-----------|
| `employee` | Default user, no special access |
| `supervisor` | Can create training requests for team members |
| `section_head` | Approval level (especially LID Section Head) |
| `department_head` | Approval level, certificate signer |
| `division_head` | Final approval for training requests |
| `director` | Highest level access |

### 2. Hardcoded Business Rules

LMS memiliki beberapa business rule yang bergantung pada nilai exact string:

| Requirement | Expected Value | LMS Feature |
|-------------|----------------|-------------|
| Admin Training Section | `section.name = "LID"` | Full access to all training reports |
| Certificate Signer (Dept Head) | `department.name = "Human Capital, General Service, Security & LID"` | Department Head signature on certificates |
| LID Division Head | `division.name = "Human Capital, Finance & General Support"` | Final approval for training requests |

### 3. User Data Structure

Minimum required fields dalam user response:

```json
{
  "id": "integer (required) - unique identifier",
  "email": "string (required) - unique email",
  "profile": {
    "nik": "string (required) - employee ID number",
    "name": "string (required) - full name",
    "avatar": "string|null - avatar URL",
    "gender": "enum: male|female"
  },
  "organization": {
    "division": {
      "id": "integer",
      "name": "string (required)"
    },
    "department": {
      "id": "integer",
      "name": "string (required)"
    },
    "section": {
      "id": "integer",
      "name": "string (required)"
    },
    "position": {
      "id": "integer",
      "name": "string (required) - must be one of allowed values"
    }
  }
}
```

---

## LMS Local Data Mapping

Saat menerima data dari API, LMS akan menyimpan ke tabel lokal dengan mapping berikut:

| API Field Path | LMS Column | Notes |
|----------------|------------|-------|
| `id` | `users.id` atau `users.external_id` | Tergantung strategi (same ID atau mapping) |
| `email` | `users.email` | Unique constraint |
| `profile.nik` | `users.nrp` | Employee number |
| `profile.name` | `users.name` | Full name |
| `organization.division.name` | `users.division` | String value |
| `organization.department.name` | `users.department` | String value |
| `organization.section.name` | `users.section` | String value |
| `organization.position.name` | `users.position` | Enum value |

### Fields NOT synced from API:

| LMS Column | Reason |
|------------|--------|
| `users.password` | Null - auth via API |
| `user_roles.role` | LMS manages own roles (instructor, admin, etc) |

---

## Security Considerations

1. **JWT Token Storage**: LMS must store JWT token securely in session for subsequent API calls.

2. **Webhook Validation**: All webhook requests must be validated using shared secret and HMAC signature.

3. **Password Handling**: LMS should NEVER receive or store actual passwords. Authentication is delegated to External API.

4. **Token Refresh**: Consider implementing token refresh mechanism for long sessions.

---

## Implementation Checklist

- [ ] External API implements `/auth/login` endpoint
- [ ] External API implements `/auth/me` endpoint  
- [ ] External API implements `/master/departments` endpoint
- [ ] External API implements `/master/sections` endpoint
- [ ] External API implements `/users` endpoint with filters
- [ ] External API implements webhook for user changes
- [ ] LMS implements `SyncUserService` for data synchronization
- [ ] LMS implements custom `LoginController` for API auth
- [ ] LMS implements webhook handler endpoint
- [ ] Both systems agree on shared webhook secret

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-14 | Initial contract draft |
