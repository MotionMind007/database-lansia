<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'survey_section_id', 'question_number', 'question_text',
        'question_type', 'is_required', 'allow_multiple',
        'dashboard_enabled', 'default_chart_type', 'is_active',
        'sort_order', 'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allow_multiple' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'is_active' => 'boolean',
        'options' => 'array',
    ];

    public static array $types = [
        'text' => 'Teks Singkat',
        'long_text' => 'Teks Panjang',
        'number' => 'Angka',
        'money' => 'Uang (Rp)',
        'date' => 'Tanggal',
        'single_choice' => 'Pilihan Tunggal',
        'multiple_choice' => 'Pilihan Ganda',
        'matrix' => 'Matrix/Tabel',
        'table_repeater' => 'Tabel Repeater',
        'file_upload' => 'Upload File',
    ];

    public static array $chartTypes = [
        'bar' => 'Bar Chart',
        'pie' => 'Pie Chart',
        'line' => 'Line Chart',
        'number_card' => 'Number Card',
        'table' => 'Tabel',
        'matrix_table' => 'Matrix Tabel',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(SurveySection::class, 'survey_section_id');
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(SurveyQuestionOption::class, 'question_id')->orderBy('sort_order');
    }
}
