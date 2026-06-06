<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Models\RespondentDocument;
use App\Support\SecureUploadStorage;
use App\Support\SurveyResponseAccess;

class DocumentController extends Controller
{
    public function show(RespondentDocument $document, SecureUploadStorage $storage)
    {
        $document->loadMissing('surveyResponse');

        if (! $document->surveyResponse || ! SurveyResponseAccess::canView(auth()->user(), $document->surveyResponse)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $storage->validPrivatePath($document->file_path, ['documents'])) {
            abort(404);
        }

        return $storage->response(
            $document->file_path,
            $document->file_name,
            $document->mime_type
        );
    }

    public function photo(Respondent $respondent, SecureUploadStorage $storage)
    {
        $responses = $respondent->surveyResponses()->get();
        $canView = $responses->contains(
            fn ($response): bool => SurveyResponseAccess::canView(auth()->user(), $response)
        );

        if (! $canView) {
            abort(403, 'Anda tidak memiliki akses ke foto ini.');
        }

        if (! $storage->validPrivatePath($respondent->photo_path, ['photos'])) {
            abort(404);
        }

        return $storage->response(
            $respondent->photo_path,
            'foto-'.$respondent->id
        );
    }
}
