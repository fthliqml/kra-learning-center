<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust as needed for auth
    }

    public function rules(): array
    {
        if ($this->input('training_type') === 'LMS') {
            return [
                'course_id' => 'required|integer|exists:courses,id',
                'training_type' => 'required',
                'group_comp' => 'required',
                'date' => 'required|string|min:10',
                'room.name' => 'nullable|string|max:100',
                'room.location' => 'nullable|string|max:150',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'participants' => 'required|array|min:1',
                'participants.*' => 'integer|exists:users,id',
            ];
        }

        $rules = [
            'training_name' => 'required|string|min:3',
            'training_type' => 'required',
            'group_comp' => 'required',
            'date' => 'required|string|min:10',
            'trainerId' => 'required|integer|exists:trainer,id',
            'room.name' => 'required|string|max:100',
            'room.location' => 'required|string|max:150',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'participants' => 'required|array|min:1',
            'participants.*' => 'integer|exists:users,id',
        ];

        if ($this->input('training_type') === 'OUT') {
            $rules['competency_id'] = 'required|integer|exists:competency,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'training_name.required' => 'Training name is required.',
            'course_id.required' => 'Course must be selected for LMS.',
            'competency_id.required' => 'Competency must be selected for Out-House.',
            'competency_id.exists' => 'Selected competency does not exist.',
            'training_type.required' => 'Training type is required.',
            'group_comp.required' => 'Group competency is required.',
            'training_name.min' => 'Training name must be at least 3 characters.',
            'date.required' => 'Training date range is required.',
            'date.min' => 'Training date range format is invalid.',
            'room.name' => 'Room name is required',
            'room.location' => 'Room location is required',
            'trainerId.required' => 'Trainer must be selected.',
            'trainerId.exists' => 'Selected trainer does not exist.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'end_time.after' => 'End time must be after start time.',
            'participants.required' => 'At least one participant must be selected.',
            'participants.array' => 'Participants must be an array of user IDs.',
            'participants.min' => 'Select at least one participant.',
            'participants.*.exists' => 'One or more selected participants are invalid.',
        ];
    }
}
