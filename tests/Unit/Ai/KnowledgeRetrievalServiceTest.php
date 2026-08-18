<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\KnowledgeRetrievalService;
use Tests\TestCase;

class KnowledgeRetrievalServiceTest extends TestCase
{
    public function test_identical_vectors_score_one(): void
    {
        $this->assertEqualsWithDelta(1.0, KnowledgeRetrievalService::cosineSimilarity([1, 0, 0], [1, 0, 0]), 0.0001);
    }

    public function test_orthogonal_vectors_score_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, KnowledgeRetrievalService::cosineSimilarity([1, 0], [0, 1]), 0.0001);
    }

    public function test_mismatched_dimensions_return_zero_instead_of_truncating(): void
    {
        // Previously this silently truncated to the shorter vector and returned a meaningless
        // score; a dimension mismatch means the stored embedding came from a different model
        // version, so it must be excluded (score 0), not scored as if it were comparable.
        $this->assertSame(0.0, KnowledgeRetrievalService::cosineSimilarity([1, 0, 0], [1, 0]));
    }

    public function test_zero_vector_returns_zero(): void
    {
        $this->assertSame(0.0, KnowledgeRetrievalService::cosineSimilarity([0, 0, 0], [1, 1, 1]));
    }
}
