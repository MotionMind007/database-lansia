<?php

return [
    'private_disk' => env('PRIVATE_UPLOAD_DISK', 'local'),
    'legacy_public_disk' => env('LEGACY_PUBLIC_UPLOAD_DISK', 'public'),

    'documents' => [
        'max_kb' => env('DOCUMENT_UPLOAD_MAX_KB', 5120),
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        'mimetypes' => ['image/jpeg', 'image/png', 'application/pdf'],
        'types' => [
            'ktp' => 'KTP',
            'kk' => 'Kartu Keluarga',
            'surat_domisili' => 'Surat Domisili',
            'foto_rumah' => 'Foto Kondisi Rumah',
        ],
    ],

    'photos' => [
        'max_kb' => env('PHOTO_UPLOAD_MAX_KB', 2048),
        'mimes' => ['jpg', 'jpeg', 'png'],
        'mimetypes' => ['image/jpeg', 'image/png'],
    ],
];
