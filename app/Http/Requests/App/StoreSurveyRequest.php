<?php

namespace App\Http\Requests\App;

class StoreSurveyRequest extends SurveyRequest
{
    public function rules(): array
    {
        return [
            'questionnaire_number' => ['required', 'string', 'unique:survey_responses,questionnaire_number'],
            ...$this->baseRules(),
        ];
    }
}
