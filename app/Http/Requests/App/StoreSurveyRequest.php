<?php

namespace App\Http\Requests\App;

class StoreSurveyRequest extends SurveyRequest
{
    public function rules(): array
    {
        return [
            'questionnaire_number' => ['required', 'string', 'unique:survey_responses,questionnaire_number'],
            'nik' => ['nullable', 'string', 'size:16', 'unique:respondents,nik'],
            ...$this->baseRules(),
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'nik.size' => 'NIK harus tepat 16 digit.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem. Responden dengan NIK yang sama tidak boleh diinput ulang.',
        ]);
    }
}
