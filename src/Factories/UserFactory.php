<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\User;
use App\Factories\Builders\UserFactoryBuilder;
use Faker\Factory as Faker;
use Faker\Generator;

class UserFactory
{
    private static Generator $faker;

    public static function create(array $attributes = []): User
    {
        if (!isset(self::$faker)) {
            self::$faker = Faker::create();
        }

        $defaultAttributes = [
            'email' => self::$faker->unique()->safeEmail(),
            'first_name' => self::$faker->firstName(),
            'last_name' => self::$faker->lastName(),
            'phone' => self::$faker->phoneNumber(),
            'status' => 'active',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return User::create($attributes);
    }

    public static function count(int $count): UserFactoryBuilder
    {
        return new UserFactoryBuilder($count);
    }
}