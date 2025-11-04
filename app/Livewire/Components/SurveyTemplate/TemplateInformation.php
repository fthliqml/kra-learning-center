<?php

namespace App\Livewire\Components\SurveyTemplate;

use Livewire\Component;
use Mary\Traits\Toast;
use App\Models\SurveyTemplate;

class TemplateInformation extends Component
{
    use Toast;

    public int $surveyLevel = 1;
    public int $surveyId = 0;

    public string $templateTitle = '';
    public ?string $templateDescription = '';
    public int $templateLevel = 1;

    public function mount(int $surveyLevel, int $surveyId)
    {
        $this->surveyLevel = $surveyLevel;
        $this->surveyId = $surveyId;

        $template = SurveyTemplate::find($this->surveyId);
        if ($template) {
            $this->templateTitle = (string) ($template->title ?? '');
            $this->templateDescription = $template->description ?? '';
            $this->templateLevel = (int) ($template->level ?? $this->surveyLevel ?? 1);
        } else {
            $this->templateLevel = (int) ($this->surveyLevel ?: 1);
        }
    }

    public function saveTemplateInfo(): void
    {
        $this->validate([
            'templateTitle' => 'required|string|max:255',
            'templateDescription' => 'nullable|string|max:2000',
            'templateLevel' => 'required|integer|in:1,2,3',
        ]);

        $previousLevel = (int) $this->surveyLevel;

        $template = SurveyTemplate::find($this->surveyId);
        if (!$template) {
            $this->error('Survey template not found.', timeout: 6000, position: 'toast-top toast-center');
            return;
        }

        $template->title = trim($this->templateTitle);
        $template->description = $this->templateDescription !== null ? trim($this->templateDescription) : null;
        $template->level = (int) $this->templateLevel;
        $template->save();

        $this->surveyLevel = (int) $template->level;

        $this->success(
            'Template information saved successfully',
            timeout: 4000,
            position: 'toast-top toast-center'
        );

        // Navigate to canonical URL if level changed
        if ($previousLevel !== (int) $template->level) {
            $this->redirectRoute(
                'survey-template.edit',
                ['level' => (int) $template->level, 'surveyId' => $template->id],
                navigate: true
            );
        }
    }

    public function render()
    {
        return view('components.survey-template.template-information');
    }

    public function placeholder()
    {
        return view('components.skeletons.template-information');
    }
}
