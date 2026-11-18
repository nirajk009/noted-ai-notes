<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Collection;

class NoteSearchService
{
    public function search(array $queryEmbedding, string $query): Collection
    {
        $matches = collect();

        // Short queries need a closer match. Ease back to the normal cutoff by 40 characters.
        $queryLength = min(mb_strlen(trim($query)), 40);
        $shortQueryBoost = 0.06 * (1 - $queryLength / 40);
        $minimumSimilarity = min(1, config('services.gemini.minimum_similarity') + $shortQueryBoost);

        // A plain PHP comparison is sufficient for this small notes database.
        $notes = Note::whereNotNull('embedding')
            ->where('embedding_model', config('services.gemini.embedding_model'))
            ->get();

        foreach ($notes as $note) {
            if (! $note->isSearchReady()) {
                continue;
            }

            $score = $this->cosineSimilarity($queryEmbedding, $note->embedding);

            if ($score >= $minimumSimilarity) {
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
