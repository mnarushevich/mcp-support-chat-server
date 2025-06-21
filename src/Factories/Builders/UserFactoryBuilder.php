<?php

declare(strict_types=1);

namespace App\Factories\Builders;

use App\Factories\UserFactory;
use Illuminate\Support\Collection;

class UserFactoryBuilder
{
    public function __construct(private readonly int $count)
    {
    }

    public function create(array $attributes = []): Collection
    {
        $users = collect();
        
        for ($i = 0; $i < $this->count; ++$i) {
            $users->push(UserFactory::create($attributes));
        }
        
        return $users;
    }
} 