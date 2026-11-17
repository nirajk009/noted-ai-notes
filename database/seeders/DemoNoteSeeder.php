<?php

namespace Database\Seeders;

use App\Exceptions\GoogleAiException;
use App\Models\Note;
use App\Services\GoogleAiService;
use Illuminate\Database\Seeder;

class DemoNoteSeeder extends Seeder
{
    public function run(GoogleAiService $google): void
    {
        $notes = [
            [
                'title' => 'Welcome to Noted',
                'content' => 'Noted is a small place for your thoughts, ideas, and plans. Choose New note to write something down. Search by describing what you remember, even if you do not remember the exact words. Open a note and choose Summarize note for a short summary with its own heading. Your notes and summaries are saved for your next visit. You can edit or delete any note whenever you like.',
            ],
            [
                'title' => 'Small ideas, worth keeping',
                'content' => 'An app that turns a long reading list into a weekly plan. A quiet dashboard for personal projects. A better way to remember useful things from conversations. Keep collecting ideas before deciding which one to build.',
            ],
        ];

        foreach ($notes as $data) {
            $note = Note::firstOrCreate(['title' => $data['title']], ['content' => $data['content']]);

            if (! $note->isSearchReady()) {
                try {
                    $google->indexNote($note);
                } catch (GoogleAiException $exception) {
                    $this->command->warn($note->title.': '.$exception->getMessage());
                }
            }
        }

        $this->command->info('Two sample notes are saved. You can edit or delete them in the app.');
    }
}
