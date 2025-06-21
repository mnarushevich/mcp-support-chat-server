<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\Chat;
use App\Models\User;
use App\Factories\Builders\ChatFactoryBuilder;
use Faker\Factory as Faker;
use Faker\Generator;

class ChatFactory
{
    private static Generator $faker;

    public static function create(array $attributes = []): Chat
    {
        if (!isset(self::$faker)) {
            self::$faker = Faker::create();
        }

        $defaultAttributes = [
            'user_id' => UserFactory::create()->id,
            'message' => self::$faker->sentence(),
            'sender_type' => self::$faker->randomElement(['user', 'agent', 'bot']),
            'timestamp' => self::$faker->dateTimeBetween('-1 year', 'now'),
            'session_id' => self::$faker->uuid(),
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return Chat::create($attributes);
    }

    public static function count(int $count): ChatFactoryBuilder
    {
        return new ChatFactoryBuilder($count);
    }
} 