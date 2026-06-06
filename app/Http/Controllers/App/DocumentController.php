<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\RespondentDocument;
use App\Support\SurveyResponseAccess;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function show(RespondentDocument $document)
    {
        $document->loadMissing('surveyResponse');

        if (! SurveyResponseAccess::canView(auth()->user(), $document->surveyResponse)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => $document->mime_type]
        );
    }
}
