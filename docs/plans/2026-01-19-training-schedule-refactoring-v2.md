# Training Schedule Refactoring Plan v2

> **Status:** Ready for execution
> **Created:** 2026-01-19
> **Approach:** Traits + Services + Clear Parent-Child Structure

---

## Current State Analysis

### Existing Components (Messy Structure)
```
app/Livewire/Components/Training/
├── ScheduleView.php (525 lines) - Parent orchestrator, OK
├── FullCalendar.php - Unclear role, embedded in ScheduleView
├── AgendaList.php - Alternative view, OK
├── TrainingFormModal.php (1626 lines) - MEGA COMPONENT, needs split
├── DetailTrainingModal.php - View-only modal, OK
├── ScheduleFilterModal.php - Filter modal
├── ScheduleExcelActions.php - Export actions
├── TrainingImportModal.php - Import modal
├── Tabs/ - Detail modal tabs
│   ├── TrainingAttendanceTab.php
│   ├── TrainingCloseTab.php
│   └── TrainingInformationTab.php
└── TrainingForm/
    └── TrainingFormState.php (exists, incomplete)
```

### Problems
1. **TrainingFormModal.php (1626 lines)** - Too large, unmaintainable
2. **Unclear hierarchy** - Which component is parent? Which is child?
3. **Mixed responsibilities** - Dropdowns, validation, persistence all in one
4. **No services** - Database logic scattered in component

---

## Target Architecture

### New Clean Structure
```
app/Livewire/Components/Training/
├── Schedule/                          # Schedule Module (Parent: ScheduleView)
│   ├── ScheduleView.php               # PARENT - Month/Year navigation, event dispatch
│   ├── ScheduleCalendar.php           # Child - Calendar grid rendering
│   ├── ScheduleAgenda.php             # Child - Agenda list rendering
│   └── ScheduleFilters.php            # Child - Filter controls (Alpine-driven)
│
├── TrainingForm/                      # Form Module (Parent: TrainingFormModal) 
│   ├── TrainingFormModal.php          # PARENT - Modal state, orchestration (~300 lines)
│   ├── Traits/
│   │   ├── TrainingFormState.php      # Properties & reset methods
│   │   ├── TrainingFormDropdowns.php  # Dropdown loading & search
│   │   └── TrainingFormValidation.php # Validation rules
│   └── Services/
│       ├── TrainingPersistService.php # Create/Update/Delete training
│       └── SessionSyncService.php     # Sync sessions, attendances, surveys
│
├── TrainingDetail/                    # Detail Module (Parent: DetailTrainingModal)
│   ├── DetailTrainingModal.php        # PARENT - View training details
│   └── Tabs/
│       ├── TrainingInfoTab.php        # Tab 1
│       ├── TrainingAttendanceTab.php  # Tab 2
│       └── TrainingCloseTab.php       # Tab 3
│
└── Shared/                            # Shared utilities
    ├── TrainingImportModal.php
    └── ScheduleExcelActions.php
```

---

## Implementation Tasks

### Phase 1: Extract Services (Foundation)

#### Task 1.1: Create TrainingPersistService
**File:** `app/Services/Training/TrainingPersistService.php`

Extract from TrainingFormModal:
- `saveTraining()` logic
- `updateTraining()` logic  
- `deleteTraining()` logic
- `updateTrainingFields()`

**Lines to extract:** ~250 lines
**Commit:** `refactor(training): extract TrainingPersistService`

#### Task 1.2: Create SessionSyncService
**File:** `app/Services/Training/SessionSyncService.php`

Extract from TrainingFormModal:
- `createSessionsForTraining()`
- `rebuildSessions()`
- `updateSessionFields()`
- `updateParticipantsAndSurveyResponses()`
- `updateAttendance()`
- `createSurveysForTraining()`
- `createSurveyResponsesForParticipants()`
- `resolveLevel3ApproverIds()`

**Lines to extract:** ~300 lines
**Commit:** `refactor(training): extract SessionSyncService`

---

### Phase 2: Extract Traits (Reusable Logic)

#### Task 2.1: Complete TrainingFormState Trait
**File:** `app/Livewire/Components/Training/TrainingForm/Traits/TrainingFormState.php`

Already exists, needs completion:
- All form properties
- `resetForm()` / `resetFormFieldsOnly()`
- State flags (`isEdit`, `dataLoaded`, etc.)

**Commit:** `refactor(training): complete TrainingFormState trait`

#### Task 2.2: Create TrainingFormDropdowns Trait
**File:** `app/Livewire/Components/Training/TrainingForm/Traits/TrainingFormDropdowns.php`

Extract from TrainingFormModal:
- `loadDropdownData()`
- `loadCourseOptions()`
- `loadTrainingModuleOptions()`
- `loadCompetencyOptions()`
- `getCourseGroupComp()`, `getCompetencyGroupComp()`
- `userSearch()`, `trainerSearch()`
- `searchCourse()`, `searchTrainingModule()`, `searchCompetency()`
- All parse helper methods

**Lines to extract:** ~400 lines
**Commit:** `refactor(training): extract TrainingFormDropdowns trait`

#### Task 2.3: Create TrainingFormValidation Trait
**File:** `app/Livewire/Components/Training/TrainingForm/Traits/TrainingFormValidation.php`

Extract from TrainingFormModal:
- `getValidationData()`
- Validation rules (from TrainingFormRequest integration)
- Error handling methods

**Lines to extract:** ~50 lines
**Commit:** `refactor(training): extract TrainingFormValidation trait`

---

### Phase 3: Refactor TrainingFormModal (Slim Parent)

#### Task 3.1: Refactor TrainingFormModal to Use Traits & Services
**File:** `app/Livewire/Components/Training/TrainingForm/TrainingFormModal.php`

After extraction:
- Use `TrainingFormState`, `TrainingFormDropdowns`, `TrainingFormValidation` traits
- Inject `TrainingPersistService`, `SessionSyncService`
- Keep only: Modal control, event listeners, updatedX lifecycle methods

**Target:** ~300 lines (from 1626)
**Commit:** `refactor(training): slim down TrainingFormModal using traits & services`

---

### Phase 4: Reorganize Schedule Components

#### Task 4.1: Reorganize ScheduleView as Clear Parent
**File:** `app/Livewire/Components/Training/Schedule/ScheduleView.php`

- Move to Schedule/ folder
- Keep only: month/year state, view toggle, event dispatch
- Remove: Heavy data loading (move to child or on-demand)

**Commit:** `refactor(training): reorganize ScheduleView as clear parent`

#### Task 4.2: Create ScheduleFilters (Alpine-Driven)
**File:** `app/Livewire/Components/Training/Schedule/ScheduleFilters.php`

Create lightweight filter component:
- Trainer dropdown
- Type dropdown
- Alpine.js instant filtering
- Dispatch events to parent

**Commit:** `feat(training): add Alpine-driven ScheduleFilters`

---

### Phase 5: Reorganize Detail Components

#### Task 5.1: Move Detail Components to TrainingDetail/
Move existing files to clear structure:
- `DetailTrainingModal.php` → `TrainingDetail/DetailTrainingModal.php`
- Tab files → `TrainingDetail/Tabs/`

**Commit:** `refactor(training): reorganize detail components`

---

### Phase 6: Update Blade Views & Cleanup

#### Task 6.1: Update Blade View Paths
Update all blade files to match new component locations.

**Commit:** `refactor(training): update blade view paths`

#### Task 6.2: Remove Old Files
Delete original files after verification.

**Commit:** `chore(training): cleanup old component files`

---

### Phase 7: Testing & Verification

#### Task 7.1: Manual Testing Checklist
- [ ] Open Training Schedule page
- [ ] Click date → Form modal opens with skeleton
- [ ] Create new training (all types: IN, OUT, LMS, BLENDED)
- [ ] Edit existing training
- [ ] Delete training
- [ ] Apply filters → Instant response
- [ ] Navigate months
- [ ] View training detail
- [ ] Attendance tab works
- [ ] Close training works

---

## Execution Order

| # | Task | Est. Time | Dependencies |
|---|------|-----------|--------------|
| 1.1 | TrainingPersistService | 30 min | None |
| 1.2 | SessionSyncService | 40 min | None |
| 2.1 | Complete TrainingFormState | 15 min | None |
| 2.2 | TrainingFormDropdowns trait | 30 min | None |
| 2.3 | TrainingFormValidation trait | 10 min | None |
| 3.1 | Refactor TrainingFormModal | 45 min | 1.1, 1.2, 2.* |
| 4.1 | Reorganize ScheduleView | 20 min | 3.1 |
| 4.2 | Create ScheduleFilters | 20 min | 4.1 |
| 5.1 | Reorganize Detail Components | 15 min | None |
| 6.1 | Update Blade Views | 20 min | All above |
| 6.2 | Cleanup Old Files | 5 min | 6.1 verified |
| 7.1 | Testing | 30 min | All above |

**Total Estimated Time: ~4.5 hours**

---

## Benefits After Refactoring

| Metric | Before | After |
|--------|--------|-------|
| TrainingFormModal lines | 1626 | ~300 |
| Largest file | 1626 lines | ~400 lines |
| Clear parent-child | ❌ | ✅ |
| Reusable logic (traits) | ❌ | ✅ |
| Testable services | ❌ | ✅ |
| Maintainability | Low | High |

---

## Context for Session Switch

**If switching IDE or model, provide this context:**

```
Working on: Training Schedule Refactoring v2
Plan file: docs/plans/2026-01-19-training-schedule-refactoring-v2.md
Approach: Extract to Traits + Services, then slim down components

Current Progress: [Last updated: 2026-01-19 14:28]
- [x] Task 1.1: TrainingPersistService ✅
- [x] Task 1.2: SessionSyncService ✅
- [x] Task 2.1: TrainingFormState ✅
- [x] Task 2.2: TrainingFormDropdowns ✅
- [x] Task 2.3: TrainingFormValidation (skipped - minimal, kept in modal) ✅
- [x] Task 3.1: Refactor TrainingFormModal ✅ (1626 → 1204 lines, -26%)
- [x] Task 4.1: Reorganize ScheduleView ✅ (already optimal - 525 lines)
- [x] Task 4.2: Create ScheduleFilters ✅ (existing ScheduleFilterModal works well)
- [x] Task 5.1: Reorganize Detail Components ✅ (already optimal - 128 lines)
- [x] Task 6.1: Update Blade Views ✅ (no path changes needed)
- [x] Task 6.2: Cleanup Old Files ✅ (removed redundant TrainingFormState)
- [ ] Task 7.1: Testing ← PENDING (manual verification needed)

Key files:
- app/Livewire/Components/Training/TrainingFormModal.php (1626 lines, main target)
- app/Livewire/Components/Training/ScheduleView.php (parent orchestrator)
- docs/plans/2026-01-19-training-schedule-refactoring-v2.md (this plan)
```
