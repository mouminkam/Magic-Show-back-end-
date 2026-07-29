<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'items' => OrderResource::collection($this->collection),
            ],
            'meta' => [
                'pagination' => [
                    'currentPage' => $this->currentPage(),
                    'limit' => $this->perPage(),
                    'totalItems' => $this->total(),
                    'totalPages' => $this->lastPage(),
                    'hasNext' => $this->hasMorePages(),
                    'hasPrev' => $this->currentPage() > 1,
                ]
            ],
        ];
    }
}

