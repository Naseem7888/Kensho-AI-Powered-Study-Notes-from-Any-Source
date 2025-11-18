<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'source_type',
        'source_reference',
        'transcript',
        'summary',
        'key_concepts',
        'study_questions',
        'difficulty_level',
        'estimated_study_time',
        'metadata',
    ];

    protected $casts = [
        'key_concepts' => 'array',
        'study_questions' => 'array',
        'metadata' => 'array',
        'estimated_study_time' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedStudyTimeAttribute(): string
    {
        $minutes = (int) $this->estimated_study_time;
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ($rem ? ' ' . $rem . ' minutes' : '');
    }

    public function scopeBySourceType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
