<?php

namespace App\Livewire\Pages\Certification;

use App\Models\CertificationParticipant;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

class CertificationHistory extends Component
{
    use Toast, WithPagination;

    public $search = '';

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function headers()
    {
        return [
            ['key' => 'no', 'label' => 'No', 'class' => '!text-center !p-4 w-[80px]'],
            ['key' => 'certification_name', 'label' => 'Certification Name', 'class' => 'w-[300px]'],
            ['key' => 'competency', 'label' => 'Competency', 'class' => '!text-center w-[200px]'],
            ['key' => 'approved_date', 'label' => 'Approved Date', 'class' => '!text-center w-[150px]'],
            ['key' => 'status', 'label' => 'Status', 'class' => '!text-center w-[120px]'],
        ];
    }

    public function histories()
    {
        $userId = Auth::id();

        $query = CertificationParticipant::query()
            ->with(['certification.certificationModule.competency', 'scores'])
            ->where('employee_id', $userId)
            ->whereHas('certification', function ($q) {
                // Show certifications that have been fully processed
                $q->whereIn('status', ['approved', 'completed']);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('certification', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('certificationModule', function ($moduleQuery) {
                            $moduleQuery->where('module_title', 'like', '%' . $this->search . '%')
                                ->orWhereHas('competency', function ($competencyQuery) {
                                    $term = '%' . $this->search . '%';
                                    $competencyQuery->where('code', 'like', $term)->orWhere('name', 'like', $term);
                                });
                        });
                });
            })
            ->latest('created_at');

        return $query->paginate(10)->through(function ($participant) {
            $certification = $participant->certification;
            $module = $certification->certificationModule;

            $competencyLabel = $module?->competency
                ? trim((string) $module->competency->code . ' - ' . (string) $module->competency->name)
                : '-';

            // Determine overall status from scores
            $status = null;
            if ($participant->scores->isNotEmpty()) {
                // Check if all scores are passed
                $allPassed = $participant->scores->every(function ($score) {
                    return $score->status === 'passed';
                });
                $status = $allPassed ? 'passed' : 'failed';
            }

            return (object) [
                'id' => $certification->id,
                'certification_name' => $certification->name,
                'competency' => $competencyLabel,
                'approved_date' => $certification->approved_at,
                'status' => $status,
            ];
        });
    }

    public function render()
    {
        return view('pages.certification.certification-history', [
            'histories' => $this->histories(),
            'headers' => $this->headers()
        ]);
    }
}
