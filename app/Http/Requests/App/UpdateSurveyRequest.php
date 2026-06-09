<?php

namespace App\Http\Requests\App;

use App\Models\SurveyResponse;
use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends SurveyRequest
{
    public function rules(): array
    {
        $response = SurveyResponse::find($this->route('id'));
        $respondentId = $response?->respondent_id;

        return [
            'questionnaire_number' => [
                'required',
                'string',
                Rule::unique('survey_responses', 'questionnaire_number')->ignore($this->route('id')),
            ],
            'nik' => [
                'nullable',
                'string',
                'size:16',
                Rule::unique('respondents', 'nik')->ignore($respondentId),
            ],
            ...$this->baseRules(),
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
        ]);
    }
}
