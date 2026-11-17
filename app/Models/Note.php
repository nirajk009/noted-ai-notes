<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'title',
        'content',
    ];

    protected $hidden = [
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function isSearchReady(): bool
    {
        return is_array($this->embedding)
            && count($this->embedding) === config('services.gemini.embedding_dimensions')
            && $this->embedding_model === config('services.gemini.embedding_model');
    }
}
