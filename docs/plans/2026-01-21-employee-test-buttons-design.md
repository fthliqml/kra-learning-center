# Employee Test Buttons Design

## Overview
Menambahkan tombol pengerjaan Pre-Test dan Post-Test pada modal detail event di Training Schedule (View Employee). Fitur ini memandu employee untuk mengerjakan tes sesuai urutan (Pre-Test dulu -> baru Post-Test) dan menampilkan nilai Pre-Test setelah selesai.

## UI/UX Requirements
1.  **Component**: Modal Detail Event (Employee Schedule).
2.  **States**:
    *   **State 1 (Belum Pre-Test)**:
        *   Tombol "Kerjakan Pre-Test" (Primary Action).
        *   Tombol Post-Test disembunyikan/disabled.
    *   **State 2 (Sudah Pre-Test)**:
        *   Info "Nilai Pre-Test: [Score]".
        *   Tombol "Kerjakan Post-Test" (Primary Action).

## Technical Implementation
1.  **Backend Logic (Component)**:
    *   Load data `TrainingAssessment` atau `TestAttempt` user untuk training tersebut.
    *   Method `hasCompletedPretest()` dan `getPretestScore()`.
    *   Action `startTest($testId)`: Redirect ke halaman pengerjaan.

2.  **Target Redirect**:
    *   Menggunakan route test runner yang sudah ada (referensi `TrainingTestList`).

## Data Flow
-   Training -> Course -> Tests (Pre/Post).
-   User -> TrainingAttendance -> TrainingAssessment (untuk status kelulusan/score).
-   User -> TestAttempt (history pengerjaan actual).
