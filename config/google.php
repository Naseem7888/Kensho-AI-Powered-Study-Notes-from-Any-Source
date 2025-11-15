<?php

return [
    'speech_to_text' => [
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'project_id' => env('GOOGLE_PROJECT_ID'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'default_language' => 'en-US',
        'supported_languages' => ['en-US', 'es-ES', 'fr-FR', 'de-DE', 'ja-JP', 'zh-CN'],
        'enable_automatic_punctuation' => true,
        'enable_word_timestamps' => true,
        'max_file_size' => 10 * 1024 * 1024,
        'supported_formats' => ['mp3', 'wav', 'flac', 'ogg', 'm4a'],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'temperature' => 0.7,
        'max_tokens' => 8192,
        'timeout' => 60,
        'json_mode' => true,
        'study_notes_schema' => [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string', 'description' => 'Concise summary (2-3 sentences)'],
                'key_concepts' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'concept' => ['type' => 'string'],
                            'explanation' => ['type' => 'string'],
                        ],
                        'required' => ['concept', 'explanation'],
                    ],
                ],
                'study_questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'minItems' => 5,
                    'maxItems' => 10,
                ],
                'difficulty_level' => [
                    'type' => 'string',
                    'enum' => ['beginner', 'intermediate', 'advanced'],
                ],
                'estimated_study_time' => ['type' => 'integer', 'description' => 'Minutes'],
            ],
            'required' => ['summary', 'key_concepts', 'study_questions', 'difficulty_level', 'estimated_study_time'],
        ],
    ],
];
