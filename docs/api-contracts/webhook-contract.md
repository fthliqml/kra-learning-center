# API Contract: Webhook for User Synchronization

Dokumen ini mendefinisikan kontrak webhook yang akan dikirim oleh API Utama ke LMS ketika ada perubahan data user.

---

## 1. Overview

Webhook berfungsi untuk menjaga sinkronisasi data user antara API Utama dan database lokal LMS secara real-time. Tanpa webhook, data hanya akan ter-update ketika user login.

### Webhook URL di LMS

```
POST https://lms.company.com/api/webhooks/user-sync
```

### Security

Semua request webhook **HARUS** menyertakan header untuk validasi:

| Header | Value | Description |
|--------|-------|-------------|
| `X-Webhook-Secret` | `{shared_secret}` | Secret key yang disepakati kedua sistem |
| `X-Webhook-Signature` | `{hmac_signature}` | HMAC-SHA256 signature dari request body |
| `Content-Type` | `application/json` | - |

---

## 2. Event: user.created

Dikirim ketika user baru dibuat di API Utama.

### Request

```
POST /api/webhooks/user-sync
X-Webhook-Secret: your_shared_secret_here
X-Webhook-Signature: sha256=abc123...
Content-Type: application/json
```

### Payload

```json
{
  "event": "user.created",
  "timestamp": "2026-01-14T08:00:00Z",
  "data": {
    "id": 500,
    "email": "new.employee@company.com",
    "profile": {
      "nik": "99999999",
      "name": "New Employee",
      "avatar": null,
      "gender": "male"
    },
    "organization": {
      "division": {
        "id": 2,
        "name": "Operations"
      },
      "department": {
        "id": 10,
        "name": "Production"
      },
      "section": {
        "id": 20,
        "name": "Blending"
      },
      "position": {
        "id": 1,
        "name": "employee"
      }
    }
  }
}
```

### Expected Response from LMS

```json
{
  "success": true,
  "message": "User created successfully",
  "user_id": 500
}
```

---

## 3. Event: user.updated

Dikirim ketika data user diupdate (profil, pindah departemen, naik jabatan, dll).

### Payload

```json
{
  "event": "user.updated",
  "timestamp": "2026-01-14T09:30:00Z",
  "data": {
    "id": 123,
    "email": "john.doe@company.com",
    "profile": {
      "nik": "12345678",
      "name": "John Doe",
      "avatar": "https://api.example.com/storage/avatars/john_new.jpg",
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
        "id": 4,
        "name": "department_head"
      }
    },
    "changes": [
      {
        "field": "position",
        "old_value": "section_head",
        "new_value": "department_head"
      }
    ]
  }
}
```

### Expected Response from LMS

```json
{
  "success": true,
  "message": "User updated successfully",
  "user_id": 123
}
```

---

## 4. Event: user.deleted

Dikirim ketika user dihapus atau di-nonaktifkan di API Utama.

### Payload

```json
{
  "event": "user.deleted",
  "timestamp": "2026-01-14T10:00:00Z",
  "data": {
    "id": 999,
    "email": "deleted.user@company.com",
    "reason": "resigned"
  }
}
```

### Expected Response from LMS

```json
{
  "success": true,
  "message": "User marked as inactive",
  "user_id": 999
}
```

### Catatan:

LMS **TIDAK BOLEH** menghapus record user karena ada Foreign Key constraint. Sebagai gantinya, LMS harus:
1. Menandai user sebagai inactive (misal: tambah kolom `is_active = false`)
2. Atau soft-delete menggunakan `deleted_at` timestamp

---

## 5. Event: organization.updated

Dikirim ketika ada perubahan struktur organisasi (rename department, merge section, dll).

### Payload

```json
{
  "event": "organization.updated",
  "timestamp": "2026-01-14T11:00:00Z",
  "data": {
    "type": "department",
    "id": 5,
    "old_name": "Human Capital, General Service, Security & LID",
    "new_name": "HC, GS, Security & Learning",
    "affected_users_count": 45
  }
}
```

### Catatan:

Jika ada event `organization.updated`, LMS perlu:
1. Update nama department/section di tabel lokal
2. Atau trigger bulk sync untuk affected users

---

## 6. Webhook Retry Policy

Jika LMS tidak merespons dengan status 2xx, API Utama harus:

| Attempt | Delay |
|---------|-------|
| 1st retry | 5 seconds |
| 2nd retry | 30 seconds |
| 3rd retry | 5 minutes |
| 4th retry | 30 minutes |
| 5th retry | 1 hour |
| After 5 retries | Log as failed, manual intervention needed |

---

## 7. LMS Webhook Handler Skeleton

Berikut adalah contoh implementasi handler di LMS (Laravel):

```php
// routes/api.php
Route::post('/webhooks/user-sync', [WebhookController::class, 'handleUserSync']);

// app/Http/Controllers/WebhookController.php
public function handleUserSync(Request $request)
{
    // 1. Validate secret
    $secret = $request->header('X-Webhook-Secret');
    if ($secret !== config('services.external_api.webhook_secret')) {
        return response()->json(['error' => 'Invalid secret'], 401);
    }

    // 2. Parse event
    $event = $request->input('event');
    $data = $request->input('data');

    // 3. Handle event
    switch ($event) {
        case 'user.created':
        case 'user.updated':
            $this->syncUserService->syncFromWebhook($data);
            break;
        case 'user.deleted':
            $this->syncUserService->deactivateUser($data['id']);
            break;
        case 'organization.updated':
            $this->syncUserService->updateOrganizationNames($data);
            break;
    }

    return response()->json(['success' => true]);
}
```

---

## 8. Summary

| Event | Trigger | LMS Action |
|-------|---------|------------|
| `user.created` | User baru dibuat di API Utama | Create user di tabel lokal dengan default role `employee` |
| `user.updated` | Profil/org user berubah | Update kolom name, nrp, section, department, division, position |
| `user.deleted` | User resign/dihapus | Soft-delete atau set `is_active = false` |
| `organization.updated` | Nama dept/section berubah | Update nama di tabel lokal atau bulk sync |
