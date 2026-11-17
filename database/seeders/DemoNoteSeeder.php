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
                'title' => 'A faster Laravel app',
                'content' => "Cache repeated database queries and add indexes to columns used in filters. Use eager loading to prevent N+1 queries. Measure response times before and after each change. Start with the slowest endpoint instead of optimizing everything at once.",
            ],
            [
                'title' => 'Small ideas, worth keeping',
                'content' => "An app that turns a long reading list into a weekly plan. A quiet dashboard for personal projects. A better way to remember useful things from conversations. Keep collecting ideas before deciding which one to build.",
            ],
            [
                'title' => 'The next chapter',
                'content' => "This month, finish the notes app and write down what I learned. Practice explaining each technical choice in plain language. Spend thirty minutes a day reading good code. Progress comes from finishing small things consistently.",
            ],
            [
                'title' => 'Sunday in the kitchen',
                'content' => "Make a simple tomato pasta. Slowly cook garlic in olive oil, add chopped tomatoes, then simmer until the sauce thickens. Save a cup of pasta water to loosen the sauce. Finish with fresh basil, black pepper, and parmesan.",
            ],
            [
                'title' => 'A few days away',
                'content' => "Plan a relaxed weekend in the mountains. Book a small place near the walking trails, pack a light jacket, and leave room for an unplanned afternoon. Check the weather before leaving. Bring a notebook and spend some time away from screens.",
            ],
            [
                'title' => 'Better conversations',
                'content' => "For the next project meeting, start with the problem we are solving. Ask one question at a time and listen before proposing a solution. End with clear decisions, a named owner for each task, and the date of the next check-in.",
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

        $this->command->info('Six sample notes are saved. You can edit or delete them in the app.');
    }
}
