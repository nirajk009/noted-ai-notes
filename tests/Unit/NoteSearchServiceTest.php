<?php

namespace Tests\Unit;

use App\Services\NoteSearchService;
use PHPUnit\Framework\TestCase;

class NoteSearchServiceTest extends TestCase
{
    public function test_vectors_pointing_in_the_same_direction_have_full_similarity(): void
    {
        $service = new NoteSearchService;

        $this->assertEqualsWithDelta(1, $service->cosineSimilarity([1, 2], [2, 4]), 0.00001);
    }

    public function test_unrelated_and_opposite_vectors_are_not_similar(): void
    {
        $service = new NoteSearchService;

        $this->assertSame(0.0, $service->cosineSimilarity([1, 0], [0, 1]));
        $this->assertSame(-1.0, $service->cosineSimilarity([1, 0], [-1, 0]));
    }

    public function test_zero_empty_and_mismatched_vectors_are_handled(): void
    {
        $service = new NoteSearchService;

        $this->assertSame(0.0, $service->cosineSimilarity([0, 0], [1, 0]));
        $this->assertSame(0.0, $service->cosineSimilarity([], []));
        $this->assertSame(0.0, $service->cosineSimilarity([1], [1, 2]));
    }
}
