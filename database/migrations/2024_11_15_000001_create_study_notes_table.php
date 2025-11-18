<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('source_type', ['youtube','audio','text']);
            $table->text('source_reference')->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary');
            $table->json('key_concepts');
            $table->json('study_questions');
            $table->enum('difficulty_level', ['beginner','intermediate','advanced']);
            $table->integer('estimated_study_time');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_notes');
    }
};
