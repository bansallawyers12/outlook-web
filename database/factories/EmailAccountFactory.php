<?php

namespace Database\Factories;

use App\Models\EmailAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['zoho', 'gmail', 'outlook']),
            'email' => $this->faker->unique()->safeEmail(),
            'access_token' => $this->faker->sha256(),
            'password' => null,
            'refresh_token' => null,
            'last_connection_error' => null,
            'last_connection_attempt' => null,
            'connection_status' => true,
        ];
    }
}
