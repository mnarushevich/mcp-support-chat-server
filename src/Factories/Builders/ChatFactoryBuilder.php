<?php

declare(strict_types=1);

namespace App\Factories\Builders;

use App\Factories\ChatFactory;
use Illuminate\Support\Collection;

class ChatFactoryBuilder
{
    public function __construct(private readonly int $count)
    {
    }

    public function create(array $attributes = []): Collection
    {
        $chats = collect();
        
        for ($i = 0; $i < $this->count; ++$i) {
            $chats->push(ChatFactory::create($attributes));
        }
        
        return $chats;
    }
} 