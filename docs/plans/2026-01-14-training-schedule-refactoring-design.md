# Training Schedule Refactoring Design

**Date:** 2026-01-14
**Status:** Approved
**Author:** AI Assistant + User Collaboration

---

## Overview

Refactoring the Training Schedule feature to improve performance, maintainability, and code organization while keeping all existing functionality intact.

## Goals

1. **Performance**: Instant modals + loading skeletons (reduce perceived latency from 500-1000ms to <50ms)
2. **Instant Filters**: Alpine.js client-side filtering (<10ms response)
3. **Maintainability**: Split mega-component (1,555 lines) into smaller focused files
4. **Clean Code**: Clear separation of concerns

## Current Problems

1. **Modal Latency**: Clicking to open modal has 500-1000ms delay with no visual feedback
2. **Filter Latency**: Applying filters requires full Livewire round-trip (300-500ms)
3. **Mega Component**: `TrainingFormModal.php` is 1,555 lines - hard to maintain
4. **Coupled Logic**: Training, session, and validation logic all mixed together
5. **Blade Duplication**: Same code duplicated for admin/non-admin views

---

## Architecture Design

### 1. New Folder Structure

```
app/Livewire/
├── Pages/Training/
│   └── Schedule.php                    # Page container (existing, minimal)
│
├── Components/Training/
│   ├── Calendar/                       # Calendar module
│   │   ├── ScheduleCalendar.php        # Main calendar orchestrator
│   │   ├── MonthGrid.php               # Month calendar grid
│   │   └── AgendaList.php              # Agenda view (existing, keep)
│   │
│   ├── TrainingForm/                   # Form components
│   │   ├── TrainingFormModal.php       # Parent modal container (~150 lines)
│   │   ├── TrainingConfigTab.php       # Tab 1: Training info
│   │   ├── SessionConfigTab.php        # Tab 2: Session/participants
│   │   └── TrainingFormState.php       # Shared state trait
│   │
│   ├── Filters/                        # Filter module
│   │   └── ScheduleFilters.php         # Alpine-driven instant filters
│   │
│   └── Shared/                         # Shared components
│       ├── TrainingDetailModal.php     # View detail (existing)
│       └── TrainingCard.php            # Calendar card (extracted)
```

### 2. Instant Modal + Loading Skeleton Pattern

**Pattern:** Hybrid Alpine + Livewire

```blade
<div x-data="{ 
    open: false,
    loading: true,
    
    openModal(payload = null) {
        this.open = true;
        this.loading = true;
        $wire.loadFormData(payload).then(() => {
            this.loading = false;
        });
    }
}"
@open-training-form.window="openModal($event.detail)">

    <x-modal x-show="open" @close="open = false">
        <template x-if="loading">
            <!-- Skeleton -->
            <div class="animate-pulse space-y-4">...</div>
        </template>
        
        <template x-if="!loading">
            <!-- Actual content -->
            @include('...')
        </template>
    </x-modal>
</div>
```

**Benefits:**
- Modal opens instantly (<50ms) via Alpine
- User sees skeleton = knows something is happening
- Data loads in background via Livewire

### 3. Alpine-Driven Instant Filters

**Pattern:** Client-side filtering with pre-loaded data

```javascript
function scheduleFilters() {
    return {
        selectedType: '',
        selectedTrainerId: '',
        
        applyFilters() {
            // Client-side, no server roundtrip
            this.$dispatch('filter-trainings', {
                type: this.selectedType,
                trainerId: this.selectedTrainerId
            });
        }
    }
}

function calendarGrid(days) {
    return {
        allDays: days,
        filteredDays: days,
        
        filterTrainings(filter) {
            // INSTANT filtering
            this.filteredDays = this.allDays.map(day => ({
                ...day,
                trainings: day.trainings.filter(t => {
                    if (filter.type && t.type !== filter.type) return false;
                    return true;
                })
            }));
        }
    }
}
```

**Benefits:**
- Filter response <10ms
- No server roundtrip for filtering
- Livewire only for month navigation (data fetch)

### 4. Component Breakdown

| Component | Responsibility | Est. Lines |
|-----------|----------------|------------|
| `TrainingFormModal.php` | Modal state, open/close, save | ~150 |
| `TrainingConfigTab.php` | Training type, module/course, date | ~200 |
| `SessionConfigTab.php` | Trainer, participants, room, time | ~200 |
| `TrainingFormState.php` | Shared properties & validation | ~150 |

**Communication Pattern:**
- **Parent → Child**: Props (`wire:model`)
- **Child → Parent**: Events (`$dispatch`)
- **Shared State**: Trait

---

## File Changes Summary

| Action | File | Notes |
|--------|------|-------|
| CREATE | `Components/Training/Calendar/ScheduleCalendar.php` | Replaces ScheduleView |
| CREATE | `Components/Training/Calendar/MonthGrid.php` | Replaces FullCalendar |
| CREATE | `Components/Training/TrainingForm/TrainingFormModal.php` | New lean parent |
| CREATE | `Components/Training/TrainingForm/TrainingConfigTab.php` | Tab 1 logic |
| CREATE | `Components/Training/TrainingForm/SessionConfigTab.php` | Tab 2 logic |
| CREATE | `Components/Training/TrainingForm/TrainingFormState.php` | Shared trait |
| CREATE | `Components/Training/Filters/ScheduleFilters.php` | Alpine filters |
| UPDATE | Blade templates | Match new structure |
| DELETE | Old ScheduleView, FullCalendar, TrainingFormModal | Cleanup after migration |

---

## Expected Improvements

| Metric | Before | After |
|--------|--------|-------|
| Modal open time | 500-1000ms | <50ms (skeleton) |
| Filter apply time | 300-500ms | <10ms (Alpine) |
| TrainingFormModal size | 1,555 lines | ~150 lines |
| Component count | 3-4 large | 7-8 small focused |
| Maintainability | Low | High |

---

## Backward Compatibility

- All functions remain identical
- API events remain compatible
- Data model unchanged
- Existing tests should pass

---

## Tech Stack

- Laravel 12.x
- Livewire 3.6
- Alpine.js (bundled with Livewire)
- Mary UI Components
- Blade Templates
