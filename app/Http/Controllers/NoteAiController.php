<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAiException;
use App\Http\Requests\SaveNoteRequest;
use App\Http\Requests\SearchNotesRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Services\GoogleAiService;
use App\Services\NoteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteAiController extends Controller
{
    public function search(SearchNotesRequest $request, GoogleAiService $google, NoteSearchService $search): AnonymousResourceCollection
    {
        $query = $request->validated('q');
        $embedding = $google->embedSearch($query);
        $matches = $search->search($embedding, $query);
        $page = $request->integer('page', 1);
        $limit = $request->integer('limit', 10);

        $notes = new LengthAwarePaginator(
            $matches->forPage($page, $limit)->values(),
            $matches->count(),
            $limit,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return NoteResource::collection($notes);
    }

    public function summaryFromText(SaveNoteRequest $request, GoogleAiService $google): JsonResponse
    {
        $note = new Note($request->validated());
        $summary = $google->summarize($note);

        return response()->json([
            'message' => 'Summary generated.',
            'data' => [
                'summary_title' => $summary['title'],
                'summary' => $summary['text'],
                'summary_model' => $summary['model'],
            ],
        ]);
    }

    public function summary(Note $note, GoogleAiService $google): JsonResponse
    {
        // Return the saved summary until the note is edited.
        if ($note->summary) {
            return response()->json(['message' => 'Saved summary loaded.', 'data' => new NoteResource($note)]);
        }

        $title = $note->title;
        $content = $note->content;
        $summary = $google->summarize($note);
        $note->refresh();

        if ($note->title !== $title || $note->content !== $content) {
            throw new GoogleAiException('The note changed during processing. Please generate its summary again.');
        }

        $note->summary = $summary['text'];
        $note->summary_title = $summary['title'];
        $note->summary_model = $summary['model'];
        $note->save();

        return response()->json(['message' => 'Summary generated and saved.', 'data' => new NoteResource($note)]);
    }

    public function embedding(Note $note, GoogleAiService $google): JsonResponse
    {
        if (! $note->isSearchReady()) {
            $google->indexNote($note);
        }

        return response()->json(['message' => 'Note is ready for semantic search.', 'data' => new NoteResource($note)]);
    }
}
