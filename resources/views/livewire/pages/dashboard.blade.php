<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\GeminiService;
use App\Services\SpeechToTextService;
use App\Services\YoutubeTranscriptService;
use App\Services\ExportService;
use App\Exceptions\ExportException;
use App\Models\StudyNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, AuthorizesRequests;

    public string $inputMode = 'youtube';
    public ?string $youtubeUrl = null;
    public $audioFile; // UploadedFile
    public ?string $textInput = null;
    public string $title = '';
    public string $language = 'en-US';
    public bool $showCreateModal = false;
    public ?int $editingNoteId = null;
    public bool $processing = false;
    public string $processingMessage = '';
    public $notes; // collection
    public $viewingNote; // StudyNote|null
    public string $exportFont = 'DejaVu Sans';
    public string $exportLayout = 'default';
    public string $exportError = '';

    // Edit-specific properties
    public string $editSummary = '';
    public array $editKeyConcepts = [];
    public array $editStudyQuestions = [];

    public function mount(): void
    {
        $this->notes = auth()->user()->studyNotes()->recent()->get();
    }

    public function rules(): array
    {
        $base = [
            'title' => ['required', 'string', 'max:255'],
            'inputMode' => ['required', 'in:youtube,audio,text'],
        ];
        if ($this->inputMode === 'youtube') {
            $base['youtubeUrl'] = ['required', 'url'];
        } elseif ($this->inputMode === 'audio') {
            $base['audioFile'] = ['required', 'file', 'mimes:mp3,wav,flac,ogg,m4a', 'max:10240'];
            $base['language'] = ['required', 'string'];
        } else {
            $base['textInput'] = ['required', 'string', 'min:50'];
        }
        return $base;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
        // Ensure Alpine modal opens reliably by dispatching the browser event
        $this->dispatch('open-modal', name: 'study-note-form');
    }

    public function createStudyNote(GeminiService $gemini, SpeechToTextService $speech, YoutubeTranscriptService $youtube): void
    {
        $this->validate();
        // Increase PHP max execution time for this request while fetching/transcribing
        // some YouTube transcripts can take longer than the default 60s. Use cautiously.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');
        $this->processing = true;
        $this->processingMessage = 'Processing input...';

        $sourceType = $this->inputMode;
        $transcript = '';
        $metadata = [];
        try {
            if ($sourceType === 'youtube') {
                try {
                    $transcriptData = $youtube->extractTranscript($this->youtubeUrl);
                    $transcript = $transcriptData['transcript'] ?? '';
                    $metadata = [
                        'video_id' => $transcriptData['video_id'] ?? null,
                        'title' => $transcriptData['title'] ?? null,
                    ];
                } catch (\App\Exceptions\YoutubeApiException $e) {
                    $this->addError('youtubeUrl', 'Failed to extract YouTube transcript: ' . $e->getMessage());
                    return;
                } catch (\App\Exceptions\YoutubeTranscriptNotFoundException $e) {
                    $this->addError('youtubeUrl', 'No transcript found for this video. Please try a different video.');
                    return;
                } catch (\App\Exceptions\InvalidYoutubeUrlException $e) {
                    $this->addError('youtubeUrl', 'Invalid YouTube URL format.');
                    return;
                }
            } elseif ($sourceType === 'audio') {
                $this->processingMessage = 'Uploading audio...';
                $path = $this->audioFile->store('audio-uploads');
                $this->processingMessage = 'Transcribing audio...';
                try {
                    $result = $speech->transcribeAudioFile(storage_path('app/' . $path), $this->language);
                    $transcript = $result['transcript'] ?? '';
                    $metadata = [
                        'language' => $result['language'] ?? $this->language,
                        'confidence' => $result['confidence'] ?? null,
                    ];
                } catch (\App\Exceptions\SpeechToTextApiException $e) {
                    $this->addError('audioFile', 'Speech-to-text API error: ' . $e->getMessage());
                    return;
                } catch (\App\Exceptions\UnsupportedAudioFormatException $e) {
                    $this->addError('audioFile', 'Unsupported audio format. Please use mp3, wav, flac, ogg, or m4a.');
                    return;
                } catch (\App\Exceptions\SpeechToTextConfigurationException $e) {
                    $this->addError('audioFile', 'Speech-to-text service is not configured properly.');
                    return;
                } finally {
                    // cleanup
                    try {
                        @unlink(storage_path('app/' . $path));
                    } catch (\Throwable $e) {
                    }
                }
            } else {
                $transcript = $this->textInput;
            }

            // Guard against empty transcript
            if (empty(trim($transcript))) {
                if ($sourceType === 'youtube') {
                    $this->addError('youtubeUrl', 'Failed to extract transcript from video. The video may not have captions available.');
                } elseif ($sourceType === 'audio') {
                    $this->addError('audioFile', 'Failed to transcribe audio. The audio may be unclear or empty.');
                } else {
                    $this->addError('textInput', 'Text input cannot be empty.');
                }
                return;
            }

            $this->processingMessage = 'Generating study notes with Gemini...';
            try {
                $structured = $gemini->generateStudyNotes($transcript, $sourceType);
            } catch (\App\Exceptions\GeminiApiException $e) {
                $this->addError('general', 'Gemini API error: ' . $e->getMessage());
                return;
            } catch (\App\Exceptions\GeminiConfigurationException $e) {
                $this->addError('general', 'Gemini service is not configured properly.');
                return;
            }

            $note = StudyNote::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'source_type' => $sourceType,
                'source_reference' => $sourceType === 'youtube' ? $this->youtubeUrl : ($sourceType === 'audio' ? $this->audioFile?->getClientOriginalName() : null),
                'transcript' => $transcript,
                'summary' => $structured['summary'] ?? '',
                'key_concepts' => $structured['key_concepts'] ?? [],
                'study_questions' => $structured['study_questions'] ?? [],
                'difficulty_level' => $structured['difficulty_level'] ?? 'beginner',
                'estimated_study_time' => (int) ($structured['estimated_study_time'] ?? 30),
                'metadata' => $metadata,
            ]);

            $this->dispatch('note-created');
            $this->notes->prepend($note);
            $this->resetForm();
            $this->showCreateModal = false;
            // Ensure the Alpine modal also closes client-side
            if (method_exists($this, 'dispatchBrowserEvent')) {
                $this->dispatchBrowserEvent('close-modal', ['name' => 'study-note-form']);
            }
        } catch (\Throwable $e) {
            $this->addError('general', 'Unexpected error: ' . $e->getMessage());
        } finally {
            $this->processing = false;
            $this->processingMessage = '';
        }
    }

    public function editStudyNote(int $id): void
    {
        $note = $this->notes->firstWhere('id', $id);
        if (!$note)
            return;

        $this->authorize('update', $note);

        $this->editingNoteId = $id;
        $this->title = $note->title;
        $this->editSummary = $note->summary;
        $this->editKeyConcepts = $note->key_concepts ?? [];
        $this->editStudyQuestions = $note->study_questions ?? [];
        $this->showCreateModal = true;
        // Ensure the modal opens client-side (x-modal listens for this event)
        $this->dispatch('open-modal', name: 'study-note-form');
        // If editing was triggered from the details view, close that modal
        $this->dispatch('close-modal', name: 'view-note-details');
    }

    public function updateStudyNote(): void
    {
        $note = $this->notes->firstWhere('id', $this->editingNoteId);
        if (!$note)
            return;

        $this->authorize('update', $note);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'editSummary' => ['required', 'string'],
            'editKeyConcepts' => ['required', 'array', 'min:1'],
            'editKeyConcepts.*.concept' => ['required', 'string'],
            'editKeyConcepts.*.explanation' => ['required', 'string'],
            'editStudyQuestions' => ['required', 'array', 'min:1'],
            'editStudyQuestions.*' => ['required', 'string'],
        ]);

        $note->update([
            'title' => $this->title,
            'summary' => $this->editSummary,
            'key_concepts' => $this->editKeyConcepts,
            'study_questions' => $this->editStudyQuestions,
        ]);

        $this->notes = auth()->user()->studyNotes()->recent()->get();
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function deleteStudyNote(int $id): void
    {
        $note = $this->notes->firstWhere('id', $id);
        if (!$note)
            return;

        $this->authorize('delete', $note);

        $note->delete();
        $this->notes = $this->notes->reject(fn($n) => $n->id === $id)->values();
    }

    public function addConcept(): void
    {
        $this->editKeyConcepts[] = ['concept' => '', 'explanation' => ''];
    }

    public function removeConcept(int $index): void
    {
        unset($this->editKeyConcepts[$index]);
        $this->editKeyConcepts = array_values($this->editKeyConcepts);
    }

    public function addQuestion(): void
    {
        $this->editStudyQuestions[] = '';
    }

    public function removeQuestion(int $index): void
    {
        unset($this->editStudyQuestions[$index]);
        $this->editStudyQuestions = array_values($this->editStudyQuestions);
    }

    public function viewNote(int $id): void
    {
        $this->viewingNote = $this->notes->firstWhere('id', $id);
        $this->dispatch('open-modal', name: 'view-note-details');
    }

    public function exportPdf(int $id, ExportService $export)
    {
        $note = $this->notes->firstWhere('id', $id);
        if (!$note)
            return;
        $this->authorize('view', $note);
        try {
            $this->exportError = '';
            return $export->pdfResponse($note, $this->exportFont, $this->exportLayout);
        } catch (ExportException $e) {
            $this->exportError = $e->getMessage();
            return null;
        }
    }

    public function exportMarkdown(int $id, ExportService $export)
    {
        $note = $this->notes->firstWhere('id', $id);
        if (!$note)
            return;
        $this->authorize('view', $note);
        try {
            $this->exportError = '';
            return $export->markdownResponse($note);
        } catch (ExportException $e) {
            $this->exportError = $e->getMessage();
            return null;
        }
    }

    public function resetForm(): void
    {
        $this->youtubeUrl = null;
        $this->audioFile = null;
        $this->textInput = null;
        $this->title = '';
        $this->language = 'en-US';
        $this->editingNoteId = null;
        $this->editSummary = '';
        $this->editKeyConcepts = [];
        $this->editStudyQuestions = [];
    }
}; ?>

<div class="dashboard-root">
    <div class="relative">
        <!-- Replaced particle canvas with auth plasma canvas (liquid gradient + ripples)
             The `initAuthBg()` in `resources/js/app.js` will auto-init when this canvas exists. -->
        <canvas id="auth-bg-canvas" class="auth-plasma-canvas" aria-hidden="true" wire:ignore
            data-use-shader="false"></canvas>

        @php($header = '<h2 class="font-semibold text-xl text-white/90 dark:text-white leading-tight">' . e(__('Study Notes')) . '</h2>')

        <div class="dashboard-content">
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-white/90">Study Notes</h1>
                        <button wire:click="openCreateModal" type="button"
                            class="px-4 py-2 rounded-md bg-indigo-600/80 hover:bg-indigo-600 text-white backdrop-blur border border-indigo-400/30 shadow relative z-50">
                            Create New Note
                        </button>
                    </div>

                    @error('general') <div class="text-red-500 text-sm">{{ $message }}</div> @enderror

                    @if($processing)
                        <div class="processing-overlay"><x-loading-spinner :message="$processingMessage" size="md" /></div>
                    @endif

                    @if($notes->isEmpty())
                        <div
                            class="bg-white/10 dark:bg-white/5 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-2xl sm:rounded-lg p-8 text-center text-gray-200">
                            <p>No study notes yet. Create your first one!</p>
                        </div>
                    @else
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($notes as $note)
                                <div class="study-note-card p-5 flex flex-col space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h2 class="font-semibold text-lg text-white/90 truncate">{{ $note->title }}</h2>
                                        <span class="text-xs text-gray-300">{{ $note->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <x-badge :type="$note->source_type" :label="ucfirst($note->source_type)" />
                                        <x-badge :type="$note->difficulty_level" :label="ucfirst($note->difficulty_level)" />
                                        <span class="text-xs text-indigo-300">{{ $note->formatted_study_time }}</span>
                                    </div>
                                    <p class="text-sm text-gray-200 line-clamp-4">{{ $note->summary }}</p>
                                    <div class="flex justify-between mt-auto pt-2">
                                        <button wire:click="viewNote({{ $note->id }})"
                                            class="text-indigo-300 hover:text-indigo-200 text-sm">View</button>
                                        <button wire:click="editStudyNote({{ $note->id }})"
                                            class="text-yellow-300 hover:text-yellow-200 text-sm">Edit</button>
                                        <button wire:click="deleteStudyNote({{ $note->id }})"
                                            class="text-red-400 hover:text-red-300 text-sm">Delete</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <x-modal name="study-note-form" :show="$showCreateModal" focusable>
        {{-- Client-side loading overlay for long-running createStudyNote requests --}}
        <div wire:loading wire:target="createStudyNote"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 pointer-events-none">
            <x-loading-spinner class="pointer-events-none" message="Processing input..." size="lg" />
        </div>
        <div class="space-y-6 relative">
            <button type="button" wire:click="$set('showCreateModal', false)" aria-label="Close"
                class="absolute top-3 right-3 rounded-md bg-white/5 hover:bg-white/10 text-white p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <h2 class="text-xl font-semibold text-white/90">
                {{ $editingNoteId ? 'Edit Study Note' : 'Create Study Note' }}
            </h2>

            @if(!$editingNoteId)
                <div class="flex space-x-2 text-sm">
                    <button type="button" wire:click="$set('inputMode','youtube')"
                        class="px-3 py-1 rounded-md border text-white/80 backdrop-blur {{ $inputMode === 'youtube' ? 'bg-indigo-600/80 border-indigo-400' : 'bg-white/10 border-white/20' }}">YouTube</button>
                    <button type="button" wire:click="$set('inputMode','audio')"
                        class="px-3 py-1 rounded-md border text-white/80 backdrop-blur {{ $inputMode === 'audio' ? 'bg-indigo-600/80 border-indigo-400' : 'bg-white/10 border-white/20' }}">Audio</button>
                    <button type="button" wire:click="$set('inputMode','text')"
                        class="px-3 py-1 rounded-md border text-white/80 backdrop-blur {{ $inputMode === 'text' ? 'bg-indigo-600/80 border-indigo-400' : 'bg-white/10 border-white/20' }}">Text</button>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-sm mb-1 text-gray-200">Title</label>
                    <input wire:model.defer="title" type="text"
                        class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2" />
                    @error('title') <x-input-error :messages="$message" /> @enderror
                </div>

                @if(!$editingNoteId)
                    @if($inputMode === 'youtube')
                        <div>
                            <label class="block text-sm mb-1 text-gray-200">YouTube URL</label>
                            <input wire:model.defer="youtubeUrl" type="url"
                                class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2"
                                placeholder="https://www.youtube.com/watch?v=..." />
                            @error('youtubeUrl') <x-input-error :messages="$message" /> @enderror
                        </div>
                    @elseif($inputMode === 'audio')
                        <div class="space-y-2">
                            <label class="block text-sm mb-1 text-gray-200">Audio File (mp3,wav,flac,ogg,m4a)</label>
                            <input wire:model="audioFile" type="file" class="w-full text-white" />
                            @error('audioFile') <x-input-error :messages="$message" /> @enderror
                            <label class="block text-sm mb-1 text-gray-200">Language</label>
                            <input wire:model.defer="language" type="text"
                                class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2" />
                        </div>
                    @else
                        <div>
                            <label class="block text-sm mb-1 text-gray-200">Text Input</label>
                            <textarea wire:model.defer="textInput" rows="6"
                                class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2"
                                placeholder="Paste or type text (min 50 chars)"></textarea>
                            @error('textInput') <x-input-error :messages="$message" /> @enderror
                        </div>
                    @endif
                @else
                    <!-- Edit Mode: Structured Fields -->
                    <div>
                        <label class="block text-sm mb-1 text-gray-200">Summary</label>
                        <textarea wire:model.defer="editSummary" rows="4"
                            class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2"></textarea>
                        @error('editSummary') <x-input-error :messages="$message" /> @enderror
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm text-gray-200">Key Concepts</label>
                            <button type="button" wire:click="addConcept"
                                class="text-xs px-2 py-1 rounded bg-indigo-600/60 hover:bg-indigo-600/80 text-white">+ Add
                                Concept</button>
                        </div>
                        @foreach($editKeyConcepts as $index => $concept)
                            <div class="space-y-2 p-3 rounded-md bg-white/5 border border-white/10">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-300">Concept {{ $index + 1 }}</span>
                                    <button type="button" wire:click="removeConcept({{ $index }})"
                                        class="text-xs text-red-400 hover:text-red-300">Remove</button>
                                </div>
                                <input wire:model.defer="editKeyConcepts.{{ $index }}.concept" type="text"
                                    placeholder="Concept name"
                                    class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm" />
                                @error('editKeyConcepts.' . $index . '.concept') <x-input-error :messages="$message" />
                                @enderror
                                <textarea wire:model.defer="editKeyConcepts.{{ $index }}.explanation" rows="2"
                                    placeholder="Explanation"
                                    class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm"></textarea>
                                @error('editKeyConcepts.' . $index . '.explanation') <x-input-error :messages="$message" />
                                @enderror
                            </div>
                        @endforeach
                        @error('editKeyConcepts') <x-input-error :messages="$message" /> @enderror
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm text-gray-200">Study Questions</label>
                            <button type="button" wire:click="addQuestion"
                                class="text-xs px-2 py-1 rounded bg-indigo-600/60 hover:bg-indigo-600/80 text-white">+
                                Add Question</button>
                        </div>
                        @foreach($editStudyQuestions as $index => $question)
                            <div class="flex items-start space-x-2">
                                <span class="text-xs text-gray-300 pt-2">{{ $index + 1 }}.</span>
                                <div class="flex-1">
                                    <input wire:model.defer="editStudyQuestions.{{ $index }}" type="text" placeholder="Question"
                                        class="w-full rounded-md bg-white/10 border border-white/20 text-white p-2 text-sm" />
                                    @error('editStudyQuestions.' . $index) <x-input-error :messages="$message" />
                                    @enderror
                                </div>
                                <button type="button" wire:click="removeQuestion({{ $index }})"
                                    class="text-xs text-red-400 hover:text-red-300 pt-2">Remove</button>
                            </div>
                        @endforeach
                        @error('editStudyQuestions') <x-input-error :messages="$message" /> @enderror
                    </div>
                @endif
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$set('showCreateModal', false)"
                    class="px-4 py-2 rounded-md bg-white/10 border border-white/20 text-white">Cancel</button>
                @if($editingNoteId)
                    <button wire:click="updateStudyNote" type="button"
                        class="px-4 py-2 rounded-md bg-indigo-600/80 hover:bg-indigo-600 text-white">Update</button>
                @else
                    <button wire:click="createStudyNote" type="button"
                        class="px-4 py-2 rounded-md bg-indigo-600/80 hover:bg-indigo-600 text-white flex items-center space-x-2"
                        wire:loading.attr="disabled" wire:target="createStudyNote">
                        <span wire:loading wire:target="createStudyNote" class="inline-flex items-center space-x-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>Creating...</span>
                        </span>
                        <span wire:loading.remove wire:target="createStudyNote">Create</span>
                    </button>
                @endif
            </div>
        </div>
    </x-modal>

    <!-- View Details Modal -->
    <x-modal name="view-note-details">
        @if($viewingNote)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-white/90">{{ $viewingNote->title }}</h2>
                <div class="flex space-x-2">
                    <x-badge :type="$viewingNote->source_type" :label="ucfirst($viewingNote->source_type)" />
                    <x-badge :type="$viewingNote->difficulty_level" :label="ucfirst($viewingNote->difficulty_level)" />
                    <span class="text-xs text-indigo-300">{{ $viewingNote->formatted_study_time }}</span>
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-white/80">Summary</h3>
                    <p class="text-sm text-gray-200">{{ $viewingNote->summary }}</p>
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-white/80">Key Concepts</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($viewingNote->key_concepts as $concept)
                            <div class="concept-card p-3 rounded-md">
                                <p class="text-xs font-semibold text-indigo-300">{{ $concept['concept'] ?? '' }}</p>
                                <p class="text-xs text-gray-200 mt-1">{{ $concept['explanation'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-white/80">Study Questions</h3>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-gray-200">
                        @foreach($viewingNote->study_questions as $q)
                            <li>{{ $q }}</li>
                        @endforeach
                    </ol>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 pt-4 space-y-2 sm:space-y-0">
                    <div class="flex space-x-2 mr-auto sm:mr-0">
                        <div class="flex items-center space-x-3">
                            <div x-data="{ open:false }" class="relative">
                                <button type="button" @click="open = !open"
                                    class="px-3 py-1 rounded-md bg-white/5 border border-white/10 text-white text-sm flex items-center space-x-2">
                                    <span>Font</span>
                                    <span class="ml-2 font-medium">{{ $exportFont }}</span>
                                </button>
                                <ul x-show="open" @click.outside="open = false" x-cloak
                                    class="absolute z-50 mt-1 bg-gray-900 rounded-md shadow-lg max-h-48 overflow-auto w-56">
                                    @foreach(['DejaVu Sans', 'DejaVu Serif', 'DejaVu Sans Mono', 'Arial', 'Times New Roman', 'Georgia', 'Courier New'] as $f)
                                        <li>
                                            <button type="button" @click="open=false"
                                                wire:click.prevent="$set('exportFont','{{ $f }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-indigo-600/60 text-white text-sm">{{ $f }}</button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div x-data="{ open:false }" class="relative">
                                <button type="button" @click="open = !open"
                                    class="px-3 py-1 rounded-md bg-white/5 border border-white/10 text-white text-sm flex items-center space-x-2">
                                    <span>Layout</span>
                                    <span class="ml-2 font-medium">{{ ucfirst($exportLayout) }}</span>
                                </button>
                                <ul x-show="open" @click.outside="open = false" x-cloak
                                    class="absolute z-50 mt-1 bg-gray-900 rounded-md shadow-lg max-h-40 overflow-auto w-48">
                                    @foreach(['default', 'classic', 'compact', 'two-column'] as $layout)
                                        <li>
                                            <button type="button" @click="open=false"
                                                wire:click.prevent="$set('exportLayout','{{ $layout }}')"
                                                class="w-full text-left px-3 py-2 hover:bg-indigo-600/60 text-white text-sm">{{ ucfirst($layout) }}</button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" wire:click="exportPdf({{ $viewingNote->id }})" wire:loading.attr="disabled"
                            wire:target="exportPdf({{ $viewingNote->id }})"
                            class="px-3 py-1 rounded-md bg-indigo-600/70 hover:bg-indigo-600 text-white text-sm">
                            <span wire:loading.remove wire:target="exportPdf({{ $viewingNote->id }})">Export PDF</span>
                            <span wire:loading wire:target="exportPdf({{ $viewingNote->id }})">Exporting PDF…</span>
                        </button>
                        <button type="button" wire:click="exportMarkdown({{ $viewingNote->id }})"
                            wire:loading.attr="disabled" wire:target="exportMarkdown({{ $viewingNote->id }})"
                            class="px-3 py-1 rounded-md bg-indigo-600/50 hover:bg-indigo-500 text-white text-sm">
                            <span wire:loading.remove wire:target="exportMarkdown({{ $viewingNote->id }})">Export
                                Markdown</span>
                            <span wire:loading wire:target="exportMarkdown({{ $viewingNote->id }})">Exporting
                                Markdown…</span>
                        </button>
                    </div>
                    @if($exportError)
                        <div class="text-red-400 text-xs sm:mr-auto">{{ $exportError }}</div>
                    @endif
                    <button type="button" wire:click="editStudyNote({{ $viewingNote->id }})"
                        class="px-3 py-1 rounded-md bg-yellow-600/70 hover:bg-yellow-600 text-white text-sm">Edit</button>
                    <button type="button" wire:click="deleteStudyNote({{ $viewingNote->id }})"
                        class="px-3 py-1 rounded-md bg-red-600/70 hover:bg-red-600 text-white text-sm">Delete</button>
                </div>
            </div>
        @endif
    </x-modal>
</div>