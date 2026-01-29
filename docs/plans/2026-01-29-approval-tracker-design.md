# Approval Tracker System - Design Document

**Date:** 2026-01-29  
**Status:** Approved  
**Target User:** Admin Only

---

## Overview

Fitur **Read-only Approval Tracker** untuk Admin, memungkinkan tracking approval yang pending/stuck di Training dan IDP (Individual Development Plan).

### Scope
- 2 halaman tracker terpisah: **Training Tracker** dan **IDP Tracker**
- Menu sidebar baru: **Trackers** (Admin Only)
- Read-only - admin hanya melihat status, tidak bisa approve/reject
- **Certification Tracker tidak termasuk** - approval certification akan dihapus

### Sidebar Structure
```
📊 Trackers (Admin Only)
   ├── Training Tracker
   └── IDP Tracker
```

---

## 1. Training Tracker

### Approval Flow
```
Training Request → Section Head LID approve → Dept Head HC approve → Fully Approved
```

### Data Display

| Column | Deskripsi | Sortable |
|--------|-----------|----------|
| Training Name | Nama training | ✅ |
| Request Date | Tanggal training dibuat | ✅ |
| Current Stage | Status saat ini | ✅ |
| Pending Approver | Nama approver yang ditunggu | ✅ |
| Days Pending | Berapa hari sudah pending | ✅ (Default sort, DESC) |

### Stage Values
- `Pending Section Head LID` - Menunggu approval Section Head LID
- `Pending Dept Head HC` - Section Head LID sudah approve, menunggu Dept Head HC
- `Approved` - Sudah fully approved
- `Rejected` - Ditolak

### Filter & Search
- **Filter by Stage** (dropdown): All, Pending Section Head LID, Pending Dept Head HC, Approved, Rejected
- **Filter by Department/Section** (dropdown): Dari section/department requester
- **Search**: Training name (text input)

### Behavior
- Default sort: Days pending (longest first) untuk highlight approval yang stuck
- Pagination: 10 items per page (standard)
- Read-only: No action buttons

---

## 2. IDP Tracker

### Approval Flow
```
Employee submit IDP plans → SPV/Section Head Area approve → Section Head LID approve → Fully Approved
```

### Data Display

| Column | Deskripsi | Sortable |
|--------|-----------|----------|
| Employee Name | Nama karyawan | ✅ |
| Employee NRP | NRP karyawan | ✅ |
| Department/Section | Departemen/section karyawan | ✅ |
| Plan Count | Jumlah plans yang disubmit | ✅ |
| Current Stage | Status IDP saat ini | ✅ |
| Pending Approver | Nama approver yang ditunggu | ✅ |
| Days Pending | Berapa hari sudah pending | ✅ (Default sort, DESC) |

### Stage Values
- `Pending SPV/Section Head` - Menunggu approval first-level approver (SPV atau Section Head Area)
- `Pending Section Head LID` - First-level approved, menunggu Section Head LID
- `Approved` - Sudah fully approved
- `Rejected` - Ditolak

### Filter & Search
- **Filter by Stage** (dropdown): All, Pending SPV/Section Head, Pending Section Head LID, Approved, Rejected
- **Filter by Department/Section** (dropdown): Dari section/department employee
- **Search**: Employee name (text input)

### Behavior
- Default sort: Days pending (longest first)
- Pagination: 10 items per page
- Read-only: No action buttons

---

## Technical Decisions

### Architecture
Mengikuti existing patterns dalam codebase:
- **Livewire Components** untuk halaman tracker (seperti existing approval pages)
- **MaryUI** untuk table components dengan sorting dan pagination
- **Blade Views** untuk templating

### File Structure
```
app/Livewire/Pages/Tracker/
├── TrainingTracker.php
└── IdpTracker.php

resources/views/pages/tracker/
├── training-tracker.blade.php
└── idp-tracker.blade.php
```

### Route Structure
```
/admin/trackers/training
/admin/trackers/idp
```

### Access Control
- Routes protected by admin middleware
- Only accessible to users with `admin` role

---

## Out of Scope
- Certification Tracker (approval akan dihapus)
- Reminder/notification system
- Escalation capability
- Admin override capability
- Export functionality

---

## UI Reference
Mengikuti style existing approval pages:
- `app/Livewire/Pages/Training/Approval.php`
- `app/Livewire/Pages/Development/DevelopmentApproval.php`

Same table styling, filter placement, and pagination pattern.
