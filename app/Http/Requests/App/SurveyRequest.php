<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

abstract class SurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function baseRules(): array
    {
        return [
            'region_id' => ['required', 'exists:regions,id'],
            'interview_date' => ['required', 'date'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'age' => ['required', 'integer', 'min:1', 'max:150'],
            ...$this->uploadValidationRules(),
        ];
    }

    protected function uploadValidationRules(): array
    {
        $documentTypes = implode(',', array_keys(config('uploads.documents.types')));

        return [
            'documents' => ['nullable', 'array:'.$documentTypes],
            'documents.*' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('uploads.documents.mimes')),
                'mimetypes:'.implode(',', config('uploads.documents.mimetypes')),
                'max:'.config('uploads.documents.max_kb'),
            ],
            'photo' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('uploads.photos.mimes')),
                'mimetypes:'.implode(',', config('uploads.photos.mimetypes')),
                'max:'.config('uploads.photos.max_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'questionnaire_number.unique' => 'Nomor kuesioner sudah digunakan.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'age.required' => 'Umur wajib diisi.',
            'documents.array' => 'Tipe dokumen tidak valid.',
            'documents.*.mimes' => 'Dokumen harus berupa JPG, PNG, atau PDF.',
            'documents.*.mimetypes' => 'Isi dokumen harus benar-benar JPG, PNG, atau PDF.',
            'documents.*.max' => 'Ukuran dokumen maksimal 5MB.',
            'photo.mimes' => 'Foto harus berupa JPG atau PNG.',
            'photo.mimetypes' => 'Isi foto harus benar-benar JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
