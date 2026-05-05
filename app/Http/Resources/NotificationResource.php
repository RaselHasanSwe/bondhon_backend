<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Defensively ensure data is always an array (handles legacy double-encoded rows)
        $data = $this->data;
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }
        if (!is_array($data)) {
            $data = [];
        }

        return [
            'id'         => $this->id,
            'type'       => $this->type,   // stored as plain string e.g. 'interest_received'
            'data'       => $data,
            'is_read'    => (bool) $this->is_read,
            'read_at'    => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

