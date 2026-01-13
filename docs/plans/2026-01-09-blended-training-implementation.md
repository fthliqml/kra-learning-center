# Blended Training Type - Implementation Plan

> **REQUIRED SUB-WORKFLOW:** Use `/execute-plan` workflow to implement this plan task-by-task.

**Goal:** Implement a new "BLENDED" training type that combines LMS online learning modules with offline classroom sessions.

**Architecture:** BLENDED training uses the existing Training model with `type='BLENDED'`, linked to a Course (for LMS content) and TrainingSessions (for offline schedules). The flow reuses Course pretest/posttest while adding offline session tracking.

**Tech Stack:** Laravel 11, Livewire 3, Blade, MySQL, Tailwind CSS

---

## Phase 1: Database & Models (3 tasks)

### Task 1.1: Update Migration - Add BLENDED to enum

**Files:**
- Modify: `database/migrations/2025_09_16_015008_create_trainings_table.php:24`

#### Step 1: Edit the migration file

Open the file and find line 24:
```php
$table->enum('type', ['IN', 'OUT', 'LMS']);
```

Change to:
```php
$table->enum('type', ['IN', 'OUT', 'LMS', 'BLENDED']);
```

#### Step 2: If database already has data, create alter migration

**Skip this if you're in development with fresh DB.**

If production, create new migration:
```bash
php artisan make:migration add_blended_to_trainings_type_enum
```

Content:
```php
public function up(): void
{
    DB::statement("ALTER TABLE trainings MODIFY COLUMN type ENUM('IN', 'OUT', 'LMS', 'BLENDED')");
}
```

#### Step 3: Run migration (if fresh DB)
```bash
php artisan migrate:fresh --seed
```

---

### Task 1.2: Update Training Model - groupComp accessor

**Files:**
- Modify: `app/Models/Training.php:80-97`

#### Step 1: Find the groupComp accessor

Locate the `groupComp()` method around line 80.

#### Step 2: Update the logic to handle BLENDED

Replace the entire method with:
```php
public function groupComp(): Attribute
{
    return Attribute::make(
        get: function () {
            $group = $this->competency?->type;

            // IN type: fallback to module competency
            if (!$group && ($this->type === 'IN' || $this->type === null)) {
                $group = $this->module?->competency?->type;
            }

            // LMS and BLENDED types: fallback to course competency
            if (!$group && in_array($this->type, ['LMS', 'BLENDED'])) {
                $group = $this->course?->competency?->type;
            }

            return $group;
        }
    );
}
```

#### Step 3: Verify syntax
```bash
php artisan tinker --execute="App\Models\Training::first()"
```

---

### Task 1.3: Update Course Model - Add helper method

**Files:**
- Modify: `app/Models/Course.php` (add new method at end of class)

#### Step 1: Add method to get associated training type

Add before the closing `}` of the class:
```php
/**
 * Get the BLENDED training associated with this course for a user.
 * Returns the training if type is BLENDED, null otherwise.
 */
public function getBlendedTrainingForUser(int $userId): ?Training
{
    return $this->trainings()
        ->where('type', 'BLENDED')
        ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
        ->with('sessions.trainer')
        ->first();
}
```

#### Step 2: Add import at top if needed
```php
use App\Models\Training;
```

---

## Phase 2: Admin Form - TrainingFormModal (5 tasks)

### Task 2.1: Add BLENDED to type options

**Files:**
- Modify: `app/Livewire/Components/Training/TrainingFormModal.php:69-73`

#### Step 1: Find trainingTypeOptions array

Locate around line 69:
```php
public $trainingTypeOptions = [
    ['id' => 'IN', 'name' => 'In-House'],
    ['id' => 'OUT', 'name' => 'Out-House'],
    ['id' => 'LMS', 'name' => 'LMS']
];
```

#### Step 2: Add BLENDED option

Change to:
```php
public $trainingTypeOptions = [
    ['id' => 'IN', 'name' => 'In-House'],
    ['id' => 'OUT', 'name' => 'Out-House'],
    ['id' => 'LMS', 'name' => 'LMS'],
    ['id' => 'BLENDED', 'name' => 'Blended'],
];
```

---

### Task 2.2: Update updatedTrainingType method

**Files:**
- Modify: `app/Livewire/Components/Training/TrainingFormModal.php:399-443`

#### Step 1: Find updatedTrainingType method

Locate around line 399.

#### Step 2: Add BLENDED handling

After the LMS handling (around line 426), add:
```php
} elseif ($value === 'BLENDED') {
    // BLENDED: needs Course + session details (like hybrid of LMS + IN)
    $this->selected_module_id = null; // Will store course_id
    $this->course_id = null;
    $this->loadCourseOptions();
}
```

#### Step 3: Update the switch that resets fields

Find line ~409:
```php
if ($value !== 'LMS' || empty($this->course_id)) {
```

Change to:
```php
if (!in_array($value, ['LMS', 'BLENDED']) || empty($this->course_id)) {
```

---

### Task 2.3: Update saveTraining method - BLENDED validation

**Files:**
- Modify: `app/Livewire/Components/Training/TrainingFormModal.php` (saveTraining method)

#### Step 1: Find validation rules section

Look for where type-specific validation happens (around line 940).

#### Step 2: Add BLENDED validation

After LMS validation block:
```php
if ($this->training_type === 'BLENDED') {
    // BLENDED requires: course_id + session details (trainer, time, room)
    $rules['course_id'] = 'required|exists:courses,id';
    $rules['trainerId'] = 'required|exists:trainers,id';
    $rules['start_time'] = 'required';
    $rules['end_time'] = 'required';
}
```

#### Step 3: Update course_id assignment

Find where course_id is set (around line 978):
```php
'course_id' => $this->training_type === 'LMS' ? $this->course_id : null,
```

Change to:
```php
'course_id' => in_array($this->training_type, ['LMS', 'BLENDED']) ? $this->course_id : null,
```

---

### Task 2.4: Update session generation for BLENDED

**Files:**
- Modify: `app/Livewire/Components/Training/TrainingFormModal.php` (rebuildSessions or session creation)

#### Step 1: Find session creation logic

Look for where TrainingSessions are created (using trainer_id, room, times).

#### Step 2: Ensure BLENDED gets full session data

BLENDED should get sessions WITH trainer/time/room (like IN, not like LMS).

Find checks like:
```php
'trainer_id' => $this->training_type === 'LMS' ? null : $this->trainerId,
```

Change to:
```php
'trainer_id' => $this->training_type === 'LMS' ? null : $this->trainerId,
// Note: BLENDED uses trainerId (not null like LMS)
```

The pattern should be: LMS = null for trainer/times, IN/OUT/BLENDED = use values.

---

### Task 2.5: Update training-form-modal.blade.php UI

**Files:**
- Modify: `resources/views/components/training/training-form-modal.blade.php:42-72`

#### Step 1: Add BLENDED case for Course selector

Find line 42:
```php
@if ($training_type === 'LMS')
```

Change to:
```php
@if ($training_type === 'LMS' || $training_type === 'BLENDED')
```

#### Step 2: Update Session Config tab

Find line 105:
```php
@if ($training_type === 'LMS')
```

Change to show full session fields for BLENDED:
```php
@if ($training_type === 'LMS')
    {{-- LMS: participants + optional room --}}
    ...
@else
    {{-- IN, OUT, BLENDED: full session config with trainer, room, times --}}
    ...
@endif
```

BLENDED should show the ELSE block (same as IN/OUT) with trainer, room, time fields.

---

## Phase 3: Calendar & Schedule Views (4 tasks)

### Task 3.1: Add purple color to full-calendar.blade.php

**Files:**
- Modify: `resources/views/components/training/full-calendar.blade.php:38-56` (and duplicate at 105-122)

#### Step 1: Find the color switch statement

Locate around line 38.

#### Step 2: Add BLENDED case BEFORE default

Insert after LMS case (line ~50):
```php
case 'BLENDED':
    $typeColor = ($isDone || $isFaded) 
        ? 'border-purple-300 bg-purple-50 hover:bg-purple-100 opacity-60'
        : 'border-purple-500 bg-purple-50 hover:bg-purple-100';
    break;
```

#### Step 3: Repeat for the second switch block (around line 105-122)

Same change for non-admin view.

---

### Task 3.2: Add purple color to agenda-list.blade.php

**Files:**
- Modify: `resources/views/components/training/agenda-list.blade.php:36-57`

#### Step 1: Find the match statement

Locate around line 36.

#### Step 2: Add BLENDED case

Insert after LMS (around line 51):
```php
'BLENDED' => [
    'dot' => $isDone || $isFaded ? 'bg-purple-300' : 'bg-purple-500',
    'badge' => $isDone || $isFaded ? 'border-purple-300 bg-purple-50' : 'border-purple-500 bg-purple-50',
],
```

---

### Task 3.3: Update schedule-filter-modal.blade.php

**Files:**
- Modify: `resources/views/components/training/schedule-filter-modal.blade.php:6-11`

#### Step 1: Find type options

Locate around line 6.

#### Step 2: Add BLENDED option

Change from:
```php
:options="[
    ['label' => 'LMS', 'value' => 'LMS'],
    ['label' => 'IN', 'value' => 'IN'],
    ['label' => 'OUT', 'value' => 'OUT'],
]"
```

To:
```php
:options="[
    ['label' => 'LMS', 'value' => 'LMS'],
    ['label' => 'IN', 'value' => 'IN'],
    ['label' => 'OUT', 'value' => 'OUT'],
    ['label' => 'Blended', 'value' => 'BLENDED'],
]"
```

---

### Task 3.4: Update ScheduleView.php filter validation

**Files:**
- Modify: `app/Livewire/Components/Training/ScheduleView.php:286` and `319`

#### Step 1: Find filter type validation

Locate around line 286:
```php
if ($this->filterType && in_array($this->filterType, ['LMS', 'IN', 'OUT'])) {
```

#### Step 2: Add BLENDED

Change to:
```php
if ($this->filterType && in_array($this->filterType, ['LMS', 'IN', 'OUT', 'BLENDED'])) {
```

#### Step 3: Repeat for line 319 (fetchAgendaTrainings)

Same change.

---

## Phase 4: Training Detail Modal (2 tasks)

### Task 4.1: Add purple badge to detail-training-modal.blade.php

**Files:**
- Modify: `resources/views/components/training/detail-training-modal.blade.php:58-70`

#### Step 1: Find badge switch

Locate around line 58.

#### Step 2: Add BLENDED case

Insert after LMS (around line 66):
```php
case 'BLENDED':
    $badge = 'bg-purple-100 text-purple-700 border border-purple-300';
    break;
```

---

### Task 4.2: Show Attendance tab for BLENDED

**Files:**
- Modify: `resources/views/components/training/detail-training-modal.blade.php:37` and `93`

#### Step 1: Find isLms check

Locate around line 27:
```php
$isLms = strtoupper($selectedEvent['type'] ?? '') === 'LMS';
```

This controls whether Attendance tab is shown. BLENDED should show Attendance (like IN).

**No change needed** - the existing logic already works:
- `$isLms` is only true for 'LMS'
- BLENDED is not LMS, so Attendance tab will show

#### Step 2: Verify logic at line 37 and 93

The `@if (!$isLms)` checks will correctly show Attendance for BLENDED.

---

## Phase 5: Training Test List (2 tasks)

### Task 5.1: Update TrainingTestList.php query

**Files:**
- Modify: `app/Livewire/Pages/TrainingTest/TrainingTestList.php:140`

#### Step 1: Find whereIn type filter

Locate around line 140:
```php
->whereIn('type', ['IN', 'LMS'])
```

#### Step 2: Add BLENDED

Change to:
```php
->whereIn('type', ['IN', 'LMS', 'BLENDED'])
```

---

### Task 5.2: Update getTestStatus for BLENDED

**Files:**
- Modify: `app/Livewire/Pages/TrainingTest/TrainingTestList.php:33-41`

#### Step 1: Find type check

Locate around line 33:
```php
if ($training->type === 'LMS' && $training->course) {
```

#### Step 2: Include BLENDED

Change to:
```php
if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
```

#### Step 3: Update posttest availability check (line 107)

Find:
```php
if ($training->type === 'LMS' && $training->course) {
```

Change to:
```php
if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
```

---

## Phase 6: Training Test List View (1 task)

### Task 6.1: Update training-test-list.blade.php

**Files:**
- Modify: `resources/views/pages/training-test/training-test-list.blade.php`

#### Step 1: Update all `$training->type === 'LMS'` checks

Find and replace (approximately 8 occurrences):

**Pattern to find:**
```php
$training->type === 'LMS'
```

**Replace with:**
```php
in_array($training->type, ['LMS', 'BLENDED'])
```

Lines to update: 47, 65, 107, 187, 198, 210, 228

#### Step 2: Update badge color (line 65)

Find:
```php
$training->type === 'LMS' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-green-100 text-green-700 border-green-200'
```

Change to:
```php
match($training->type) {
    'LMS' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'BLENDED' => 'bg-purple-100 text-purple-700 border-purple-200',
    default => 'bg-green-100 text-green-700 border-green-200',
}
```

---

## Phase 7: Test Review (3 tasks)

### Task 7.1: Update TestReviewList.php

**Files:**
- Modify: `app/Livewire/Pages/TestReview/TestReviewList.php`

#### Step 1: Update whereIn (line 45)

Find:
```php
->whereIn('type', ['IN', 'LMS'])
```

Change to:
```php
->whereIn('type', ['IN', 'LMS', 'BLENDED'])
```

#### Step 2: Update getReviewStats (line 172)

Find:
```php
if ($training->type === 'LMS' && $training->course) {
```

Change to:
```php
if (in_array($training->type, ['LMS', 'BLENDED']) && $training->course) {
```

#### Step 3: Update getLatestPendingSubmission (line 220)

Same pattern change.

#### Step 4: Update typeOptions (line 155)

Add BLENDED to options:
```php
'typeOptions' => [
    ['value' => '', 'label' => 'All Types'],
    ['value' => 'IN', 'label' => 'In-House'],
    ['value' => 'LMS', 'label' => 'LMS'],
    ['value' => 'BLENDED', 'label' => 'Blended'],
],
```

---

### Task 7.2: Update TestReviewParticipants.php

**Files:**
- Modify: `app/Livewire/Pages/TestReview/TestReviewParticipants.php:25` and `66`

#### Step 1: Find LMS type checks

Lines 25 and 66:
```php
if ($training->type === 'LMS') {
```

#### Step 2: Include BLENDED

Change to:
```php
if (in_array($training->type, ['LMS', 'BLENDED'])) {
```

---

### Task 7.3: Update test-review-list.blade.php badge

**Files:**
- Modify: `resources/views/pages/test-review/test-review-list.blade.php:81-82`

#### Step 1: Find badge class

Locate around line 81:
```php
class="badge badge-sm {{ $training->type === 'IN' ? 'badge-success' : 'badge-info' }}"
```

#### Step 2: Update to include BLENDED

Change to:
```php
class="badge badge-sm {{ match($training->type) {
    'IN' => 'badge-success',
    'LMS' => 'badge-info',
    'BLENDED' => 'bg-purple-100 text-purple-700',
    default => 'badge-info',
} }}"
```

---

## Phase 8: Course Views (3 tasks)

### Task 8.1: Update Overview.php - Load BLENDED training

**Files:**
- Modify: `app/Livewire/Pages/Courses/Overview.php:43-55`

#### Step 1: Add property for training

Add after line 17:
```php
public ?Training $blendedTraining = null;
```

#### Step 2: Add import

At top:
```php
use App\Models\Training;
```

#### Step 3: Load BLENDED training in mount()

Add after line 55 (after checking isAssigned):
```php
// Load BLENDED training for this course (if any) to show offline schedule
$this->blendedTraining = $course->getBlendedTrainingForUser($userId);
```

---

### Task 8.2: Update overview.blade.php - Add schedule section

**Files:**
- Modify: `resources/views/pages/courses/overview.blade.php`

#### Step 1: Add BLENDED badge after title (around line 14)

After line 14 (title), add:
```blade
@if ($blendedTraining)
    <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 border border-purple-200 text-xs px-2.5 py-1 rounded-full font-medium ml-3">
        <x-icon name="o-academic-cap" class="size-3.5" />
        Blended Training
    </span>
@endif
```

#### Step 2: Add offline schedule section (after Learning Modules, around line 155)

Insert after `</section>` of Learning Modules:
```blade
{{-- Jadwal Kelas Offline (BLENDED only) --}}
@if ($blendedTraining && $blendedTraining->sessions->isNotEmpty())
<section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mt-5">
    <h2 class="text-base md:text-lg font-semibold mb-4 flex items-center gap-2">
        <x-icon name="o-calendar-days" class="size-5 text-purple-500" />
        Jadwal Kelas Offline
    </h2>
    <div class="space-y-3">
        @foreach ($blendedTraining->sessions->sortBy('day_number') as $session)
        <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-200 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-900">Sesi {{ $session->day_number }}</span>
                <span class="text-xs text-gray-500">{{ $session->date?->format('d M Y') }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="size-4 text-gray-400" />
                    {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i') : '-' }} - 
                    {{ $session->end_time ? \Carbon\Carbon::parse($session->end_time)->format('H:i') : '-' }}
                </div>
                <div class="flex items-center gap-2">
                    <x-icon name="o-map-pin" class="size-4 text-gray-400" />
                    {{ $session->room_name ?: '-' }}{{ $session->room_location ? ', ' . $session->room_location : '' }}
                </div>
                @if ($session->trainer)
                <div class="flex items-center gap-2 col-span-2">
                    <x-icon name="o-user" class="size-4 text-gray-400" />
                    {{ $session->trainer->name ?? $session->trainer->user?->name ?? '-' }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
```

---

### Task 8.3: Update course cards - Add BLENDED badge

**Files:**
- Modify: `resources/views/components/courses/list-card.blade.php:30-35`
- Modify: `resources/views/components/courses/grid-card.blade.php` (similar location)

#### Step 1: Detect if course is BLENDED

Add at top of file (after line 8):
```php
// Check if this course has a BLENDED training for the user
$isBlended = $course->trainings()
    ->where('type', 'BLENDED')
    ->whereHas('assessments', fn($q) => $q->where('employee_id', $userId))
    ->exists();
```

#### Step 2: Add badge in list-card.blade.php (after line 34)

After the competency badge, add:
```blade
@if ($isBlended)
    <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 border border-purple-200 px-2 py-0.5 text-[10px] font-medium">
        Blended
    </span>
@endif
```

#### Step 3: Repeat for grid-card.blade.php

Same changes.

---

## Phase 9: Exports & Services (2 tasks)

### Task 9.1: Update TrainingExport.php

**Files:**
- Modify: `app/Exports/TrainingExport.php:39-49`

#### Step 1: Update type checks

BLENDED should show:
- course_title (like LMS)
- trainer_name, times (like IN)

Find line 39:
```php
'training_name' => $training->type === 'LMS' ? '' : $training->name,
```

Change to:
```php
'training_name' => in_array($training->type, ['LMS', 'BLENDED']) 
    ? ($training->course?->title ?? $training->name) 
    : $training->name,
```

For trainer/times (lines 44, 47, 48):
```php
'trainer_name' => $training->type === 'LMS' ? '' : ...,
```

Change to:
```php
'trainer_name' => $training->type === 'LMS' ? '' : ...,
// Keep as-is: BLENDED shows trainer (not LMS)
```

For course_title (line 49):
```php
'course_title' => $training->type === 'LMS' ? ... : '',
```

Change to:
```php
'course_title' => in_array($training->type, ['LMS', 'BLENDED']) ? ... : '',
```

---

### Task 9.2: Update TrainingCertificateService.php

**Files:**
- Modify: `app/Services/TrainingCertificateService.php:297-310`

#### Step 1: Find resolveCompetencyId method

Locate around line 285.

#### Step 2: Update LMS check

Find:
```php
if ($type === 'LMS' || $type === '') {
```

Change to:
```php
if (in_array($type, ['LMS', 'BLENDED']) || $type === '') {
```

---

## Phase 10: Final Verification (1 task)

### Task 10.1: Full System Test

#### Step 1: Clear caches
```bash
php artisan optimize:clear
```

#### Step 2: Test Admin Flow
1. Go to Training Schedule
2. Click "Add New Training"
3. Select type "Blended"
4. Verify: Course dropdown appears
5. Verify: Session config tab shows trainer, room, time fields
6. Create a BLENDED training with participants

#### Step 3: Test User Flow
1. Login as participant
2. Go to Courses menu
3. Verify: BLENDED course shows purple "Blended" badge
4. Click course → Overview
5. Verify: "Jadwal Kelas Offline" section appears
6. Go to Training Test menu
7. Verify: BLENDED training appears with purple badge
8. Verify: Pretest redirects to Course flow

#### Step 4: Test Calendar
1. Go to Training Schedule (calendar view)
2. Verify: BLENDED trainings appear in purple
3. Click a BLENDED training
4. Verify: Detail modal shows Attendance tab

---

## Summary Checklist

| Phase | Tasks | Status |
|-------|-------|--------|
| 1. Database & Models | 3 tasks | ⬜ |
| 2. Admin Form | 5 tasks | ⬜ |
| 3. Calendar Views | 4 tasks | ⬜ |
| 4. Detail Modal | 2 tasks | ⬜ |
| 5. Training Test List | 2 tasks | ⬜ |
| 6. Training Test View | 1 task | ⬜ |
| 7. Test Review | 3 tasks | ⬜ |
| 8. Course Views | 3 tasks | ⬜ |
| 9. Exports & Services | 2 tasks | ⬜ |
| 10. Verification | 1 task | ⬜ |
| **TOTAL** | **26 tasks** | |

---

## Color Reference

| Type | Border | Background | Text |
|------|--------|------------|------|
| IN | green-500 | green-50 | green-700 |
| OUT | amber-500 | amber-50 | amber-700 |
| LMS | indigo-500 | indigo-50 | indigo-700 |
| **BLENDED** | **purple-500** | **purple-50** | **purple-700** |
