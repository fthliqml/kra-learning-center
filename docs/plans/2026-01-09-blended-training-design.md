# Design Document: Blended Training Type

**Date:** 2026-01-09  
**Status:** Approved  
**Author:** Brainstorming Session

---

## Overview

Implementasi tipe training baru **BLENDED** yang menggabungkan:
- **LMS Online Learning**: Modul pembelajaran di website (video, PDF, quiz per section)
- **Offline Classroom**: Pertemuan langsung dengan trainer (seperti Training IN)

---

## User Journey Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        BLENDED TRAINING                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PreTest ─┬─→ Learning Modules (bebas, kapan saja)              │
│           │                                                     │
│           └─→ Kelas Offline (sesuai jadwal) ← parallel          │
│                                                                 │
│  ↓ (Semua modules selesai)                                      │
│                                                                 │
│  PostTest → Result                                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Syarat Unlock PostTest
- ✅ Semua Learning Modules selesai (WAJIB)
- ⚪ Attendance offline TIDAK mempengaruhi unlock PostTest

### Dimana BLENDED Muncul?
| Lokasi | Apa yang ditampilkan |
|--------|---------------------|
| **Training Schedule** | Jadwal offline (sessions) dengan warna purple |
| **Courses** | Course dengan badge "Blended" + learning modules + info jadwal |
| **Training Test** | Pretest/Posttest → redirect ke Course flow (bukan test IN) |

---

## Data Model

### Training Entity (type='BLENDED')

```
Training (type='BLENDED')
├── course_id → Course (LMS content: topics, sections, pretest/posttest)
├── module_id → NULL (tidak pakai TrainingModule)
├── sessions → TrainingSessions (jadwal offline dengan trainer, room, time)
└── assessments → TrainingAssessment (peserta)
```

### Database Changes

**File:** `database/migrations/2025_09_16_015008_create_trainings_table.php`

```php
// Edit enum untuk include BLENDED
$table->enum('type', ['IN', 'OUT', 'LMS', 'BLENDED']);
```

---

## Admin Flow

### Training Form Modal
**File:** `app/Livewire/Components/Training/TrainingFormModal.php`

**Tambah opsi BLENDED:**
```php
public $trainingTypeOptions = [
    ['id' => 'IN', 'name' => 'In-House'],
    ['id' => 'OUT', 'name' => 'Out-House'],
    ['id' => 'LMS', 'name' => 'LMS'],
    ['id' => 'BLENDED', 'name' => 'Blended'],  // NEW
];
```

**Logic saat type = BLENDED:**
1. Tampilkan dropdown **Course** (wajib) → auto-fill nama training dari course title
2. Tampilkan field **jadwal offline** (date range, start_time, end_time, trainer, room) → wajib
3. Saat save → generate **TrainingSessions** per hari (seperti IN)
4. Sync `group_comp` dari `course.competency.type`

---

## User-Facing UI

### 1. Course Listing (Menu Courses)
**Files:** 
- `resources/views/components/courses/list-card.blade.php`
- `resources/views/components/courses/grid-card.blade.php`

**Badge untuk BLENDED:**
```html
<span class="bg-purple-100 text-purple-700 border border-purple-200 text-[10px] px-2 py-0.5 rounded-full font-medium">
    Blended
</span>
```

### 2. Course Overview
**File:** `resources/views/pages/courses/overview.blade.php`

**Tambahan untuk BLENDED:**
- Badge "Blended Training" di header
- Section baru: **Jadwal Kelas Offline**
  - List semua sessions
  - Per session tampilkan: tanggal, waktu, ruangan, lokasi, trainer

```blade
{{-- Jadwal Kelas Offline Section --}}
@if ($training && $training->type === 'BLENDED')
<section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <h2 class="text-base md:text-lg font-semibold mb-4 flex items-center gap-2">
        <x-icon name="o-calendar" class="size-5 text-purple-500" />
        Jadwal Kelas Offline
    </h2>
    <div class="space-y-3">
        @foreach ($training->sessions as $session)
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="font-medium text-gray-900">Sesi {{ $session->day_number }}</div>
            <div class="text-sm text-gray-600 mt-1 space-y-1">
                <div>📅 {{ $session->date->format('d M Y') }}</div>
                <div>🕐 {{ $session->start_time }} - {{ $session->end_time }}</div>
                <div>📍 {{ $session->room_name }}, {{ $session->room_location }}</div>
                <div>👤 {{ $session->trainer->name ?? '-' }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
```

### 3. Training Test List
**File:** `app/Livewire/Pages/TrainingTest/TrainingTestList.php`

**Update query untuk include BLENDED:**
```php
->whereIn('type', ['IN', 'LMS', 'BLENDED'])
```

**View file:** `resources/views/pages/training-test/training-test-list.blade.php`

**Logic redirect:**
- Type `IN` → existing IN test page
- Type `LMS` atau `BLENDED` → redirect ke Course flow (`courses-pretest.index` / `courses-posttest.index`)

### 4. Training Schedule (Calendar)
**File:** `resources/views/components/training/full-calendar.blade.php`

**Warna BLENDED (purple):**
```php
case 'BLENDED':
    $typeColor = ($isDone || $isFaded) 
        ? 'border-purple-300 bg-purple-50 hover:bg-purple-100 opacity-60'
        : 'border-purple-500 bg-purple-50 hover:bg-purple-100';
    break;
```

### 5. Schedule Filter
**File:** `resources/views/components/training/schedule-filter-modal.blade.php`

**Tambah opsi filter BLENDED:**
```php
['id' => 'BLENDED', 'name' => 'Blended']
```

### 6. ScheduleView Query
**File:** `app/Livewire/Components/Training/ScheduleView.php`

**Update filter validation:**
```php
if ($this->filterType && in_array($this->filterType, ['LMS', 'IN', 'OUT', 'BLENDED'])) {
    $query->where('type', $this->filterType);
}
```

### 7. Training Detail Modal
**File:** `resources/views/components/training/detail-training-modal.blade.php`

**Badge styling untuk BLENDED:**
```php
case 'BLENDED':
    $typeBadgeClass = 'bg-purple-100 text-purple-700 border-purple-200';
    break;
```

---

## Color Scheme Summary

| Type | Border | Background | Hover |
|------|--------|------------|-------|
| IN | `green-500` | `green-50` | `green-100` |
| OUT | `amber-500` | `amber-50` | `amber-100` |
| LMS | `indigo-500` | `indigo-50` | `indigo-100` |
| **BLENDED** | `purple-500` | `purple-50` | `purple-100` |

---

## Files to Modify (COMPREHENSIVE LIST)

### Database & Models (3 files)
1. `database/migrations/2025_09_16_015008_create_trainings_table.php` - Add BLENDED to enum
2. `app/Models/Training.php` - Update groupComp accessor to handle BLENDED
3. `app/Models/Course.php` - May need method to check if associated training is BLENDED

### Livewire Components - Training (3 files)
4. `app/Livewire/Components/Training/TrainingFormModal.php` - Add BLENDED type + form logic
   - Update `$trainingTypeOptions` array
   - Add logic for BLENDED: select Course + jadwal offline fields
   - Update `updatedTrainingType()` method
   - Update `saveTraining()` method
   - Update `rebuildSessions()` method
5. `app/Livewire/Components/Training/ScheduleView.php` - Update filter validation to include BLENDED
6. `app/Livewire/Components/Training/ScheduleFilterModal.php` (if exists) - Add BLENDED to type options

### Livewire Pages - Training Test (1 file)
7. `app/Livewire/Pages/TrainingTest/TrainingTestList.php`
   - Update `whereIn('type', ['IN', 'LMS'])` to include `'BLENDED'`
   - Update `getTestStatus()` method to handle BLENDED → use course tests (like LMS)

### Livewire Pages - Test Review (3 files)
8. `app/Livewire/Pages/TestReview/TestReviewList.php`
   - Update `whereIn('type', ['IN', 'LMS'])` to include `'BLENDED'`
   - Update `getReviewStats()` to handle BLENDED → use course tests
   - Update `getLatestPendingSubmission()` to handle BLENDED
   - Update `typeOptions` array
9. `app/Livewire/Pages/TestReview/TestReviewParticipants.php`
   - Update type checks: `$training->type === 'LMS'` should also match `'BLENDED'`
10. `app/Livewire/Pages/TestReview/TestReviewAnswers.php`
    - Update type checks for BLENDED

### Livewire Pages - Courses (1 file)
11. `app/Livewire/Pages/Courses/Overview.php`
    - Load training data for BLENDED to display offline schedule

### Exports & Imports (2 files)
12. `app/Exports/TrainingExport.php`
    - Update type checks: BLENDED should show course_title like LMS but also trainer/times like IN
13. `app/Imports/TrainingImport.php`
    - Update to support importing BLENDED type
    - Handle BLENDED logic: course_id + session fields

### Services (1 file)
14. `app/Services/TrainingCertificateService.php`
    - Update type checks in `resolveCompetencyId()` for BLENDED

### Frontend Views - Training (5 files)
15. `resources/views/components/training/training-form-modal.blade.php`
    - Add UI for BLENDED: show Course selector + offline session fields (trainer, room, time)
16. `resources/views/components/training/full-calendar.blade.php`
    - Add purple color case for BLENDED type
17. `resources/views/components/training/detail-training-modal.blade.php`
    - Add purple badge for BLENDED
    - BLENDED should show Attendance tab (like IN, unlike LMS)
18. `resources/views/components/training/schedule-filter-modal.blade.php`
    - Add `['label' => 'Blended', 'value' => 'BLENDED']` to type options
19. `resources/views/components/training/agenda-list.blade.php`
    - Add purple color case for BLENDED type

### Frontend Views - Courses (3 files)
20. `resources/views/components/courses/list-card.blade.php`
    - Add BLENDED badge detection and display
21. `resources/views/components/courses/grid-card.blade.php`
    - Add BLENDED badge detection and display
22. `resources/views/pages/courses/overview.blade.php`
    - Add "Jadwal Kelas Offline" section for BLENDED
    - Needs to load training + sessions data

### Frontend Views - Training Test (1 file)
23. `resources/views/pages/training-test/training-test-list.blade.php`
    - Update `$training->type === 'LMS'` checks to also match `'BLENDED'`
    - BLENDED redirects to course flow (like LMS)
    - Add purple badge for BLENDED

### Frontend Views - Test Review (2 files)
24. `resources/views/pages/test-review/test-review-list.blade.php`
    - Update badge styling for BLENDED (purple)
    - Update type display text
25. `resources/views/pages/test-review/test-review-participants.blade.php`
    - Update badge styling for BLENDED

---

## Summary: Total Files to Modify

| Category | Count |
|----------|-------|
| Database & Models | 2-3 |
| Livewire Components | 3 |
| Livewire Pages | 5 |
| Exports/Imports | 2 |
| Services | 1 |
| Frontend Views | 11 |
| **TOTAL** | **24-25 files** |

---

## Out of Scope (YAGNI)

- ❌ Attendance validation untuk unlock PostTest
- ❌ Partial completion tracking untuk offline sessions
- ❌ Hybrid pretest (online+offline)
- ❌ Menu terpisah khusus Blended

---

## Next Steps

1. Run `/write-plan` untuk membuat implementation tasks
2. Delegate ke specialist workflows untuk implementasi
