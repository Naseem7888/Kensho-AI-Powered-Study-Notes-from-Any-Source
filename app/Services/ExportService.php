<?php

namespace App\Services;

use App\Exceptions\ExportException;
use App\Models\StudyNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ExportService
{
    public function pdfResponse(StudyNote $note, ?string $font = null)
    {
        try {
            $fileName = $this->fileName($note, 'pdf');
            $pdf = Pdf::loadView('exports.study-note-pdf', [
                'note' => $note,
                'font' => $font,
            ]);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            throw new ExportException('Failed to generate PDF: ' . $e->getMessage(), 0, $e);
        }
    }

    public function markdownResponse(StudyNote $note)
    {
        try {
            $fileName = $this->fileName($note, 'md');
            $markdown = $this->buildMarkdown($note);

            return response()->streamDownload(function () use ($markdown) {
                echo $markdown;
            }, $fileName, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            throw new ExportException('Failed to generate Markdown: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function fileName(StudyNote $note, string $ext): string
    {
        $base = Str::slug($note->title ?: 'study-note');
        return $base . '.' . ltrim($ext, '.');
    }

    protected function buildMarkdown(StudyNote $note): string
    {
        $lines = [];
        $lines[] = '# ' . ($note->title ?: 'Study Note');
        $lines[] = '';
        if ($note->summary) {
            $lines[] = '## Summary';
            $lines[] = $note->summary;
            $lines[] = '';
        }
        if (!empty($note->key_concepts)) {
            $lines[] = '## Key Concepts';
            foreach ($note->key_concepts as $concept) {
                $title = $concept['concept'] ?? '';
                $desc = $concept['explanation'] ?? '';
                $lines[] = '- **' . $title . '**: ' . $desc;
            }
            $lines[] = '';
        }
        if (!empty($note->study_questions)) {
            $lines[] = '## Study Questions';
            foreach ($note->study_questions as $q) {
                $qtext = is_array($q) ? ($q['question'] ?? '') : (string)$q;
                $lines[] = '- ' . $qtext;
            }
            $lines[] = '';
        }
        // Optional transcript section
        if (!empty($note->transcript)) {
            $lines[] = '## Transcript';
            $lines[] = '```';
            $lines[] = (string) $note->transcript;
            $lines[] = '```';
            $lines[] = '';
        }
        $lines[] = '---';
        $lines[] = '**Source**: ' . ucfirst((string) $note->source_type);
        if ($note->created_at) {
            $lines[] = '**Created**: ' . $note->created_at->toDateTimeString();
        }
        if (!empty($note->difficulty_level)) {
            $lines[] = '**Difficulty**: ' . ucfirst((string) $note->difficulty_level);
        }
        if (!is_null($note->estimated_study_time)) {
            // Use accessor for human readable value if available
            $lines[] = '**Estimated Study Time**: ' . ($note->formatted_study_time ?? ($note->estimated_study_time . ' min'));
        }
        return implode("\n", $lines) . "\n";
    }
}
