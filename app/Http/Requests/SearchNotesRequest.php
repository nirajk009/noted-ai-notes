<?php

namespace App\Http\Requests;

class SearchNotesRequest extends ListNotesRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'q' => ['required', 'string', 'max:500'],
        ]);
    }
}
