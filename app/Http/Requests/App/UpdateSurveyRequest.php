<?php

namespace App\Http\Requests\App;

use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends SurveyRequest
{
    public function rules(): array
    {
        return [
            'questionnaire_number' => [
                'required',
                'string',
                Rule::unique('survey_responses', 'questionnaire_number')->ignore($this->route('id')),
            ],
            ...$this->baseRules(),
        ];
    }
}
