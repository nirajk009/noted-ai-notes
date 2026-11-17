<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Collection;

class NoteSearchService
{
    public function search(array $queryEmbedding): Collection
    {
        $matches = collect();

        // A plain PHP comparison is sufficient for this small notes database.
        $notes = Note::whereNotNull('embedding')
            ->where('embedding_model', config('services.gemini.embedding_model'))
            ->get();

        foreach ($notes as $note) {
            if (! $note->isSearchReady()) {
                continue;
            }

            $score = $this->cosineSimilarity($queryEmbedding, $note->embedding);

            if ($score >= config('services.gemini.minimum_similarity')) {
                $note->setAttribute('similarity', round($score, 4));
                $matches->push($note);
            }
        }

        return $matches->sortByDesc('similarity')->values();
    }

    public function cosineSimilarity(array $first, array $second): float
    {
        if (count($first) !== count($second) || count($first) === 0) {
            return 0;
        }

        $dotProduct = 0;
        $firstMagnitude = 0;
        $secondMagnitude = 0;

        foreach ($first as $index => $value) {
            $dotProduct += $value * $second[$index];
            $firstMagnitude += $value * $value;
            $secondMagnitude += $second[$index] * $second[$index];
        }

        if ($firstMagnitude == 0 || $secondMagnitude == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($firstMagnitude) * sqrt($secondMagnitude));
    }
}
