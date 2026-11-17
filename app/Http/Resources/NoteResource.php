<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'summary_title' => $this->summary_title,
            'summary_model' => $this->summary_model,
            'search_ready' => $this->resource->isSearchReady(),
            'similarity' => $this->when($this->resource->getAttribute('similarity') !== null, $this->similarity),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
