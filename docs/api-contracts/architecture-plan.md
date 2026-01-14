# Architecture Plan: User Management Integration

Dokumen ini menjelaskan arsitektur teknis untuk mengintegrasikan LMS (Learning Center) dengan External User Management API.

---

## 1. Current State Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     CURRENT STATE (Monolithic)                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    LMS (Learning Center)                         │   │
│  │                                                                  │   │
│  │  ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐   │   │
│  │  │ Auth System  │    │ User Model   │    │ Training Module  │   │   │
│  │  │              │    │              │    │                  │   │   │
│  │  │ - Login Form │───►│ - id         │◄───│ - Requests       │   │   │
│  │  │ - Session    │    │ - name       │    │ - Assessments    │   │   │
│  │  │ - Password   │    │ - email      │    │ - Certificates   │   │   │
│  │  │   Hashing    │    │ - password   │    │                  │   │   │
│  │  └──────────────┘    │ - section    │    └──────────────────┘   │   │
│  │                      │ - department │                            │   │
│  │                      │ - division   │    ┌──────────────────┐   │   │
│  │                      │ - position   │◄───│ Survey Module    │   │   │
│  │                      └──────────────┘    └──────────────────┘   │   │
│  │                             │                                    │   │
│  │                             ▼                                    │   │
│  │                      ┌──────────────┐                            │   │
│  │                      │   MySQL DB   │                            │   │
│  │                      │   (Local)    │                            │   │
│  │                      └──────────────┘                            │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  Problems:                                                              │
│  ❌ User data managed independently (not synced with main system)      │
│  ❌ Password stored locally (security concern)                         │
│  ❌ Manual user creation (no SSO)                                      │
│  ❌ Organization changes not reflected automatically                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Target State Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                   TARGET STATE (Microservice Consumer)                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌────────────────────────┐         ┌────────────────────────────────┐ │
│  │    EXTERNAL API        │         │     LMS (Learning Center)      │ │
│  │    (Master System)     │         │     (Sub-Web / Consumer)       │ │
│  │                        │         │                                │ │
│  │  ┌──────────────────┐  │   JWT   │  ┌──────────────────────────┐  │ │
│  │  │ Auth Service     │◄─┼─────────┼──│ ExternalAuthService      │  │ │
│  │  │ POST /auth/login │  │         │  │ - login(email, pass)     │  │ │
│  │  │ GET /auth/me     │──┼─────────┼─►│ - fetchProfile(token)    │  │ │
│  │  └──────────────────┘  │         │  └──────────────────────────┘  │ │
│  │                        │         │              │                  │ │
│  │  ┌──────────────────┐  │         │              ▼                  │ │
│  │  │ User Service     │  │ Webhook │  ┌──────────────────────────┐  │ │
│  │  │ - CRUD Users     │──┼─────────┼─►│ SyncUserService          │  │ │
│  │  │ - Profile Update │  │         │  │ - syncFromLogin()        │  │ │
│  │  └──────────────────┘  │         │  │ - syncFromWebhook()      │  │ │
│  │                        │         │  │ - deactivateUser()       │  │ │
│  │  ┌──────────────────┐  │         │  └──────────────────────────┘  │ │
│  │  │ Master Data      │  │  REST   │              │                  │ │
│  │  │ GET /departments │◄─┼─────────┼──│            ▼                  │ │
│  │  │ GET /sections    │  │         │  ┌──────────────────────────┐  │ │
│  │  └──────────────────┘  │         │  │ Local Users Table        │  │ │
│  │                        │         │  │ (Synchronized Mirror)    │  │ │
│  │  ┌──────────────────┐  │         │  │                          │  │ │
│  │  │ Master Database  │  │         │  │ - id (same as external)  │  │ │
│  │  │ (Source of Truth)│  │         │  │ - email, name, nrp       │  │ │
│  │  └──────────────────┘  │         │  │ - section, dept, div     │  │ │
│  │                        │         │  │ - position               │  │ │
│  └────────────────────────┘         │  │ - password = NULL        │  │ │
│                                     │  │ - role (LMS-specific)    │  │ │
│                                     │  └──────────────────────────┘  │ │
│                                     │              │                  │ │
│                                     │              ▼                  │ │
│                                     │  ┌──────────────────────────┐  │ │
│                                     │  │ Training/Survey Modules  │  │ │
│                                     │  │ (FK relations preserved) │  │ │
│                                     │  └──────────────────────────┘  │ │
│                                     └────────────────────────────────┘ │
│                                                                         │
│  Benefits:                                                              │
│  ✅ Single source of truth for user data                               │
│  ✅ No password stored in LMS (more secure)                            │
│  ✅ Auto-sync when org structure changes                               │
│  ✅ Existing FK relations preserved (no breaking changes)              │
│  ✅ LMS-specific roles (instructor, admin) managed locally             │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         LOGIN SEQUENCE DIAGRAM                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  User          LMS Frontend       LMS Backend        External API       │
│   │                 │                  │                   │            │
│   │  1. Submit      │                  │                   │            │
│   │  email/pass     │                  │                   │            │
│   │ ───────────────►│                  │                   │            │
│   │                 │                  │                   │            │
│   │                 │  2. POST /login  │                   │            │
│   │                 │ ────────────────►│                   │            │
│   │                 │                  │                   │            │
│   │                 │                  │  3. POST /api/    │            │
│   │                 │                  │     auth/login    │            │
│   │                 │                  │ ─────────────────►│            │
│   │                 │                  │                   │            │
│   │                 │                  │  4. Return JWT +  │            │
│   │                 │                  │     User Data     │            │
│   │                 │                  │ ◄─────────────────│            │
│   │                 │                  │                   │            │
│   │                 │                  │  5. SyncUserService            │
│   │                 │                  │     - Upsert local user        │
│   │                 │                  │     - Map org data             │
│   │                 │                  │     - Keep LMS role            │
│   │                 │                  │                   │            │
│   │                 │                  │  6. Auth::login()              │
│   │                 │                  │     (Laravel session)          │
│   │                 │                  │                   │            │
│   │                 │  7. Redirect     │                   │            │
│   │                 │     to Dashboard │                   │            │
│   │                 │ ◄────────────────│                   │            │
│   │                 │                  │                   │            │
│   │  8. Dashboard   │                  │                   │            │
│   │ ◄───────────────│                  │                   │            │
│   │                 │                  │                   │            │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Data Synchronization Strategy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      SYNC TRIGGERS & MECHANISMS                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    SYNC-ON-LOGIN (Primary)                       │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │                                                                  │   │
│  │  Trigger: Every successful login                                 │   │
│  │                                                                  │   │
│  │  Process:                                                        │   │
│  │  1. API returns user data with latest profile                    │   │
│  │  2. SyncUserService checks if user exists locally                │   │
│  │     - EXISTS: Update name, section, dept, div, position          │   │
│  │     - NOT EXISTS: Create new user with default role 'employee'   │   │
│  │  3. LMS-specific data (role, signature) NOT touched              │   │
│  │                                                                  │   │
│  │  Pros: Always fresh data on active users                         │   │
│  │  Cons: Inactive users not updated until they login               │   │
│  │                                                                  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    WEBHOOK (Real-time)                           │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │                                                                  │   │
│  │  Trigger: External API sends notification on user changes        │   │
│  │                                                                  │   │
│  │  Events:                                                         │   │
│  │  ┌─────────────────┬────────────────────────────────────────┐   │   │
│  │  │ Event           │ LMS Action                             │   │   │
│  │  ├─────────────────┼────────────────────────────────────────┤   │   │
│  │  │ user.created    │ Create local user (default role)       │   │   │
│  │  │ user.updated    │ Update profile fields                  │   │   │
│  │  │ user.deleted    │ Soft-delete (is_active = false)        │   │   │
│  │  │ org.updated     │ Bulk update affected users             │   │   │
│  │  └─────────────────┴────────────────────────────────────────┘   │   │
│  │                                                                  │   │
│  │  Pros: Immediate sync, even for inactive users                   │   │
│  │  Cons: Requires API team to implement webhook sender             │   │
│  │                                                                  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    SCHEDULED SYNC (Fallback)                     │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │                                                                  │   │
│  │  Trigger: Laravel Scheduler (daily at midnight)                  │   │
│  │                                                                  │   │
│  │  Process:                                                        │   │
│  │  1. Fetch all users from GET /api/users?limit=all                │   │
│  │  2. Bulk upsert to local users table                             │   │
│  │  3. Mark users not in API response as inactive                   │   │
│  │                                                                  │   │
│  │  Pros: Catches any missed updates                                │   │
│  │  Cons: Heavy operation, not real-time                            │   │
│  │                                                                  │   │
│  │  Recommendation: Optional, only if webhook unreliable            │   │
│  │                                                                  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Database Schema Changes

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      USERS TABLE MIGRATION                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  BEFORE (Current):                                                      │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ users                                                           │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ id           BIGINT UNSIGNED PK AUTO_INCREMENT                  │    │
│  │ name         VARCHAR(255) NOT NULL                              │    │
│  │ nrp          INTEGER NOT NULL                                   │    │
│  │ email        VARCHAR(255) UNIQUE NOT NULL                       │    │
│  │ password     VARCHAR(255) NOT NULL  ◄── Local auth              │    │
│  │ section      VARCHAR(255) NULLABLE                              │    │
│  │ department   VARCHAR(255) NULLABLE                              │    │
│  │ division     VARCHAR(255) NULLABLE                              │    │
│  │ position     ENUM(...) DEFAULT 'employee'                       │    │
│  │ created_at   TIMESTAMP                                          │    │
│  │ updated_at   TIMESTAMP                                          │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  AFTER (Migration):                                                     │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ users                                                           │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ id           BIGINT UNSIGNED PK  ◄── Same as external API ID   │    │
│  │ external_id  BIGINT NULLABLE INDEX ◄── Optional backup mapping │    │
│  │ name         VARCHAR(255) NOT NULL                              │    │
│  │ nrp          VARCHAR(50) NOT NULL  ◄── Changed to string (NIK) │    │
│  │ email        VARCHAR(255) UNIQUE NOT NULL                       │    │
│  │ password     VARCHAR(255) NULLABLE ◄── NULL for API auth       │    │
│  │ section      VARCHAR(255) NULLABLE                              │    │
│  │ department   VARCHAR(255) NULLABLE                              │    │
│  │ division     VARCHAR(255) NULLABLE                              │    │
│  │ position     ENUM(...) DEFAULT 'employee'                       │    │
│  │ is_active    BOOLEAN DEFAULT TRUE  ◄── For soft-disable         │    │
│  │ last_synced  TIMESTAMP NULLABLE    ◄── Track sync time          │    │
│  │ created_at   TIMESTAMP                                          │    │
│  │ updated_at   TIMESTAMP                                          │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  Migration Script:                                                      │
│  1. ALTER password column to NULLABLE                                   │
│  2. ADD external_id column (indexed)                                    │
│  3. ADD is_active column (default true)                                 │
│  4. ADD last_synced column                                              │
│  5. SET password = NULL for all existing users (will re-auth via API)  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Service Layer Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      NEW SERVICE CLASSES                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  app/Services/                                                          │
│  ├── ExternalAuthService.php                                            │
│  │   │                                                                  │
│  │   ├── login(string $email, string $password): AuthResult            │
│  │   │   └── POST to external /api/auth/login                          │
│  │   │                                                                  │
│  │   ├── fetchProfile(string $token): UserData                         │
│  │   │   └── GET to external /api/auth/me                              │
│  │   │                                                                  │
│  │   └── logout(string $token): bool                                   │
│  │       └── POST to external /api/auth/logout                         │
│  │                                                                      │
│  ├── SyncUserService.php                                                │
│  │   │                                                                  │
│  │   ├── syncFromLogin(UserData $data): User                           │
│  │   │   └── Create or update local user from API response             │
│  │   │                                                                  │
│  │   ├── syncFromWebhook(array $payload): User                         │
│  │   │   └── Handle webhook event and update local user                │
│  │   │                                                                  │
│  │   ├── deactivateUser(int $userId): bool                             │
│  │   │   └── Mark user as inactive (soft-delete)                       │
│  │   │                                                                  │
│  │   └── mapExternalToLocal(UserData $external): array                 │
│  │       └── Transform API response to local column names              │
│  │                                                                      │
│  └── ExternalApiClient.php                                              │
│      │                                                                  │
│      ├── get(string $endpoint, array $params = []): Response           │
│      ├── post(string $endpoint, array $data = []): Response            │
│      └── withToken(string $token): self                                │
│          └── Base HTTP client with auth header handling                │
│                                                                         │
│  app/Http/Controllers/                                                  │
│  ├── Auth/                                                              │
│  │   └── LoginController.php  (Override)                               │
│  │       ├── showLoginForm(): View                                     │
│  │       ├── login(Request $request): RedirectResponse                 │
│  │       │   └── Use ExternalAuthService instead of local auth         │
│  │       └── logout(): RedirectResponse                                │
│  │                                                                      │
│  └── WebhookController.php  (New)                                       │
│      └── handleUserSync(Request $request): JsonResponse                │
│          └── Validate secret, dispatch to SyncUserService              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Configuration

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      CONFIG & ENVIRONMENT                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  .env                                                                   │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ # External API Configuration                                    │    │
│  │ EXTERNAL_API_BASE_URL=https://api.company.com                   │    │
│  │ EXTERNAL_API_TIMEOUT=30                                         │    │
│  │ EXTERNAL_API_WEBHOOK_SECRET=your_shared_secret_here             │    │
│  │                                                                 │    │
│  │ # Token Storage (session key)                                   │    │
│  │ EXTERNAL_API_TOKEN_KEY=external_api_token                       │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  config/services.php                                                    │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ 'external_api' => [                                             │    │
│  │     'base_url' => env('EXTERNAL_API_BASE_URL'),                 │    │
│  │     'timeout' => env('EXTERNAL_API_TIMEOUT', 30),               │    │
│  │     'webhook_secret' => env('EXTERNAL_API_WEBHOOK_SECRET'),     │    │
│  │     'endpoints' => [                                            │    │
│  │         'login' => '/api/auth/login',                           │    │
│  │         'me' => '/api/auth/me',                                 │    │
│  │         'logout' => '/api/auth/logout',                         │    │
│  │         'users' => '/api/users',                                │    │
│  │         'departments' => '/api/master/departments',             │    │
│  │         'sections' => '/api/master/sections',                   │    │
│  │     ],                                                          │    │
│  │ ],                                                              │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Implementation Phases

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      IMPLEMENTATION ROADMAP                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  PHASE 1: Foundation (Week 1)                                           │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ □ Create database migration (nullable password, new columns)    │    │
│  │ □ Create ExternalApiClient service                              │    │
│  │ □ Create ExternalAuthService                                    │    │
│  │ □ Create SyncUserService                                        │    │
│  │ □ Add config/services.php entries                               │    │
│  │ □ Add .env.example with new variables                           │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  PHASE 2: Authentication (Week 2)                                       │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ □ Override LoginController with API auth                        │    │
│  │ □ Implement sync-on-login flow                                  │    │
│  │ □ Test login with external API                                  │    │
│  │ □ Handle error cases (API down, invalid credentials)            │    │
│  │ □ Update login view if needed                                   │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  PHASE 3: Webhook Integration (Week 3)                                  │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ □ Create WebhookController                                      │    │
│  │ □ Add webhook route (excluded from CSRF)                        │    │
│  │ □ Implement event handlers (created, updated, deleted)          │    │
│  │ □ Test with mock webhooks                                       │    │
│  │ □ Coordinate with API team for real webhooks                    │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  PHASE 4: Master Data Integration (Week 4)                              │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ □ Update reporting dropdowns to use API                         │    │
│  │ □ OR: Cache master data locally with periodic refresh           │    │
│  │ □ Test department/section filters in reports                    │    │
│  │ □ Test user search in training forms                            │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  PHASE 5: Testing & Migration (Week 5)                                  │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ □ Run full integration tests                                    │    │
│  │ □ Run migration on staging                                      │    │
│  │ □ Nullify passwords for existing users                          │    │
│  │ □ User acceptance testing                                       │    │
│  │ □ Deploy to production                                          │    │
│  │ □ Monitor sync logs                                             │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| External API downtime | Users cannot login | Implement fallback to cached credentials (optional) |
| Webhook delivery failure | Data out of sync | Implement retry mechanism + scheduled sync job |
| ID collision (local vs external) | Data integrity issues | Use external ID as primary, or create mapping table |
| Performance degradation | Slow login | Cache user data aggressively, async sync |
| Security breach | Data leak | Use HTTPS only, validate webhook signatures |

---

## 10. Rollback Plan

If critical issues arise:

1. **Immediate**: Revert LoginController to use local auth
2. **Database**: Run reverse migration to restore password requirement
3. **Data**: Re-hash passwords from backup (if available) or trigger password reset for all users
