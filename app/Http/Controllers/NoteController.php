<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAiException;
use App\Http\Requests\ListNotesRequest;
use App\Http\Requests\SaveNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Services\GoogleAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    public function index(ListNotesRequest $request): AnonymousResourceCollection
    {
        $notes = Note::orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($request->integer('limit', 10))
            ->withQueryString();

        return NoteResource::collection($notes);
    }

    public function store(SaveNoteRequest $request, GoogleAiService $google): JsonResponse
    {
        // Save first so an AI outage never loses the user's note.
        $note = Note::create($request->validated());
        $warning = $this->indexNote($note, $google);

        return response()->json([
            'message' => 'Note created.',
            'data' => new NoteResource($note),
            'warning' => $warning,
        ], 201);
    }

    public function show(Note $note): NoteResource
    {
        return new NoteResource($note);
    }

    public function update(SaveNoteRequest $request, Note $note, GoogleAiService $google): JsonResponse
    {
        $note->fill($request->validated());
        $changed = $note->isDirty(['title', 'content']);

        if ($changed) {
            $note->embedding = null;
            $note->embedding_model = null;
            $note->summary = null;
            $note->summary_title = null;
            $note->summary_model = null;
        }

        $note->save();
        $warning = null;

        if ($changed || ! $note->isSearchReady()) {
            $warning = $this->indexNote($note, $google);
        }

        return response()->json([
            'message' => 'Note updated.',
            'data' => new NoteResource($note),
            'warning' => $warning,
        ]);
    }

    public function destroy(Note $note): Response
    {
        $note->delete();

        return response()->noContent();
    }

    private function indexNote(Note $note, GoogleAiService $google): ?string
    {
        try {
            $google->indexNote($note);

            return null;
        } catch (GoogleAiException $exception) {
            return 'Your note is saved, but semantic search is not ready for it. '.$exception->getMessage();
        }
    }
}
