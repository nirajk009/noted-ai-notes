<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_a_note_and_its_embedding_are_saved_in_the_database(): void
    {
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([1, 0])]])]);

        $response = $this->postJson('/api/notes', ['title' => 'Laravel caching', 'content' => 'Cache repeated queries.']);

        $response->assertCreated()->assertJsonPath('data.search_ready', true)->assertJsonPath('warning', null);
        $response->assertJsonMissingPath('data.embedding');
        $note = Note::findOrFail($response->json('data.id'));
        $this->assertSame($this->vector([1, 0]), $note->embedding);
        $this->assertSame('gemini-embedding-2', $note->embedding_model);
        $this->assertDatabaseHas('notes', ['title' => 'Laravel caching', 'content' => 'Cache repeated queries.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), 'gemini-embedding-2:embedContent')
                && $request->hasHeader('x-goog-api-key', 'testing-key')
                && $request['content']['parts'][0]['text'] === 'title: Laravel caching | text: Cache repeated queries.'
                && $request['outputDimensionality'] === 768;
        });
    }

    public function test_google_failure_does_not_lose_a_note(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'private provider details']], 429)]);

        $response = $this->postJson('/api/notes', ['title' => 'Keep this', 'content' => 'My work must be saved.']);

        $response->assertCreated()->assertJsonPath('data.search_ready', false);
        $this->assertStringContainsString('Your note is saved', $response->json('warning'));
        $response->assertDontSee('private provider details');
        $this->getJson('/api/notes/'.$response->json('data.id'))->assertOk()->assertJsonPath('data.content', 'My work must be saved.');
        $this->assertDatabaseHas('notes', ['title' => 'Keep this', 'embedding' => null]);
    }

    public function test_required_fields_and_length_limits_are_validated(): void
    {
        $this->postJson('/api/notes', ['title' => ' ', 'content' => ' '])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'content']);

        $this->postJson('/api/notes', ['title' => str_repeat('a', 256), 'content' => str_repeat('b', 12001)])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'content']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_lists_are_paginated_and_unknown_notes_return_json(): void
    {
        for ($index = 1; $index <= 3; $index++) {
            Note::create(['title' => 'Note '.$index, 'content' => 'Stored content.']);
        }

        $this->getJson('/api/notes?page=2&limit=2')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.current_page', 2);
        $this->getJson('/api/notes?page=0&limit=51')->assertUnprocessable()->assertJsonValidationErrors(['page', 'limit']);
        $this->get('/api/notes/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_editing_clears_the_old_summary_and_replaces_the_embedding(): void
    {
        $note = $this->indexedNote('Old title', [1, 0]);
        $note->summary = 'Old summary';
        $note->save();
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([0, 1])]])]);

        $this->putJson('/api/notes/'.$note->id, ['title' => 'New title', 'content' => 'New content'])
            ->assertOk()->assertJsonPath('data.summary', null)->assertJsonPath('data.search_ready', true);

        $this->assertSame($this->vector([0, 1]), $note->fresh()->embedding);
        $this->assertNull($note->fresh()->summary);
    }

    public function test_unchanged_notes_reuse_the_saved_embedding_and_summary(): void
    {
        $note = $this->indexedNote('Same title', [1, 0]);
        $note->summary = 'Keep this summary.';
        $note->save();

        $this->putJson('/api/notes/'.$note->id, ['title' => $note->title, 'content' => $note->content])
            ->assertOk()->assertJsonPath('data.summary', 'Keep this summary.');
        Http::assertNothingSent();
    }

    public function test_a_failed_edit_embedding_never_leaves_an_outdated_vector(): void
    {
        $note = $this->indexedNote('Original', [1, 0]);
        Http::fake(['*' => Http::response([], 503)]);

        $this->putJson('/api/notes/'.$note->id, ['title' => 'Changed', 'content' => 'Different meaning'])
            ->assertOk()->assertJsonPath('data.search_ready', false);

        $this->assertNull($note->fresh()->embedding);
        $this->assertSame('Different meaning', $note->fresh()->content);
    }

    public function test_a_pending_embedding_can_be_retried(): void
    {
        $note = Note::create(['title' => 'Pending', 'content' => 'Saved without AI.']);
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([1, 0])]])]);

        $this->postJson('/api/notes/'.$note->id.'/embedding')->assertOk()->assertJsonPath('data.search_ready', true);
        $this->assertNotNull($note->fresh()->embedding);
    }

    public function test_summaries_are_saved_and_reused_without_another_api_call(): void
    {
        $note = Note::create(['title' => 'Performance', 'content' => 'Cache repeated queries and add database indexes.']);
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Cache queries and index the database.']]], 'finishReason' => 'STOP']],
        ])]);

        $this->postJson('/api/notes/'.$note->id.'/summary')->assertOk()
            ->assertJsonPath('data.summary', 'Cache queries and index the database.');
        $this->postJson('/api/notes/'.$note->id.'/summary')->assertOk();
        $this->assertSame('Cache queries and index the database.', $note->fresh()->summary);
        Http::assertSentCount(1);
    }

    public function test_incomplete_summaries_are_not_saved(): void
    {
        $note = Note::create(['title' => 'Test', 'content' => 'A complete thought.']);
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Incomplete']]], 'finishReason' => 'MAX_TOKENS']],
        ])]);

        $this->postJson('/api/notes/'.$note->id.'/summary')->assertStatus(502);
        $this->assertNull($note->fresh()->summary);
    }

    public function test_summary_models_fall_back_in_the_requested_order(): void
    {
        $note = Note::create(['title' => 'Fallback', 'content' => 'Keep the important decisions.']);
        Http::fake([
            '*/gemini-4-hydrogen:generateContent' => Http::response([], 503),
            '*/gemini-3.8-flash:generateContent' => Http::response([], 429),
            '*/gemini-3.7-flash:generateContent' => Http::response([], 404),
            '*/gemini-3.6-flash:generateContent' => Http::response([], 503),
            '*/gemini-3.1-flash-lite:generateContent' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Keep the key decisions.']]], 'finishReason' => 'STOP']],
            ]),
        ]);

        $this->postJson('/api/notes/'.$note->id.'/summary')->assertOk()
            ->assertJsonPath('data.summary', 'Keep the key decisions.')
            ->assertJsonPath('data.summary_model', 'gemini-3.1-flash-lite');

        $models = Http::recorded()->map(function ($record) {
            return basename($record[0]->url());
        })->all();

        $this->assertSame([
            'gemini-4-hydrogen:generateContent',
            'gemini-3.8-flash:generateContent',
            'gemini-3.7-flash:generateContent',
            'gemini-3.6-flash:generateContent',
            'gemini-3.1-flash-lite:generateContent',
        ], $models);
        $this->assertSame('gemini-3.1-flash-lite', $note->fresh()->summary_model);
    }

    public function test_busy_models_have_a_short_cooldown_and_success_stops_fallback(): void
    {
        config(['services.gemini.summary_models' => ['gemini-4-hydrogen', 'gemini-3.8-flash']]);
        Http::fake([
            '*/gemini-4-hydrogen:generateContent' => Http::response([], 503),
            '*/gemini-3.8-flash:generateContent' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'A complete summary.']]], 'finishReason' => 'STOP']],
            ]),
        ]);

        for ($index = 1; $index <= 2; $index++) {
            $note = Note::create(['title' => 'Note '.$index, 'content' => 'Some content to summarize.']);
            $this->postJson('/api/notes/'.$note->id.'/summary')->assertOk()
                ->assertJsonPath('data.summary_model', 'gemini-3.8-flash');
        }

        // One failed Hydrogen request, then two successful Flash requests.
        Http::assertSentCount(3);

        $this->travel(301)->seconds();
        $note = Note::create(['title' => 'After cooldown', 'content' => 'Try the preferred model again.']);
        $this->postJson('/api/notes/'.$note->id.'/summary')->assertOk();
        Http::assertSentCount(5);
    }

    public function test_query_embeddings_are_cached_but_results_use_current_database_rows(): void
    {
        $this->indexedNote('First result', [1, 0]);
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([1, 0])]])]);

        $this->getJson('/api/notes/search?q=faster')->assertOk()->assertJsonPath('meta.total', 1);
        $this->indexedNote('New result', [1, 0]);
        $this->getJson('/api/notes/search?q=faster')->assertOk()->assertJsonPath('meta.total', 2);
        Http::assertSentCount(1);

        $this->travel(3601)->seconds();
        $this->getJson('/api/notes/search?q=faster')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_semantic_search_ranks_meaning_matches_and_supports_pagination(): void
    {
        $best = $this->indexedNote('Caching', [1, 0]);
        $second = $this->indexedNote('Database indexes', [0.8, 0.2]);
        $this->indexedNote('A cake recipe', [-1, 0]);
        Note::create(['title' => 'Not indexed', 'content' => 'Still pending.']);
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([1, 0])]])]);

        $this->getJson('/api/notes/search?q=make%20my%20app%20faster&limit=1')
            ->assertOk()->assertJsonPath('data.0.id', $best->id)->assertJsonPath('meta.total', 2);
        $this->getJson('/api/notes/search?q=make%20my%20app%20faster&limit=1&page=2')
            ->assertOk()->assertJsonPath('data.0.id', $second->id)->assertJsonCount(1, 'data');

        Http::assertSent(function ($request) {
            return $request['content']['parts'][0]['text'] === 'task: search result | query: make my app faster';
        });
    }

    public function test_search_rejects_missing_queries_and_malformed_embeddings(): void
    {
        $this->getJson('/api/notes/search')->assertUnprocessable()->assertJsonValidationErrors(['q']);
        Http::fake(['*' => Http::response(['embedding' => ['values' => [1, 2]]])]);
        $this->getJson('/api/notes/search?q=test')->assertStatus(502)->assertJsonStructure(['message']);
    }

    public function test_google_outages_return_a_safe_search_error(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'private provider details']], 503)]);

        $this->getJson('/api/notes/search?q=test')->assertStatus(503)->assertJsonStructure(['message'])
            ->assertDontSee('private provider details')->assertDontSee('testing-key');
    }

    public function test_ai_endpoints_are_rate_limited(): void
    {
        RateLimiter::for('ai', function () {
            return Limit::perMinute(1)->by('rate-limit-test');
        });
        Http::fake(['*' => Http::response(['embedding' => ['values' => $this->vector([1, 0])]])]);

        $this->getJson('/api/notes/search?q=test')->assertOk();
        $this->getJson('/api/notes/search?q=test')->assertStatus(429);
        Http::assertSentCount(1);
    }

    public function test_deleting_a_note_removes_it_from_mysql(): void
    {
        $note = Note::create(['title' => 'Delete me', 'content' => 'Stored content']);

        $this->deleteJson('/api/notes/'.$note->id)->assertNoContent();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
        $this->getJson('/api/notes/'.$note->id)->assertNotFound();
    }

    private function vector(array $values): array
    {
        return array_pad($values, 768, 0);
    }

    private function indexedNote(string $title, array $values): Note
    {
        $note = Note::create(['title' => $title, 'content' => 'A note about '.$title]);
        $note->embedding = $this->vector($values);
        $note->embedding_model = 'gemini-embedding-2';
        $note->save();

        return $note;
    }
}
