<?php

namespace Database\Factories;

use App\Models\User;
use App\Traits\SchemaManagement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    use SchemaManagement;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => User::factory(),
            'schema_name' => str_replace(' ', '_', strtolower($this->faker->unique()->company())),
            'personal_team' => true,
        ];
    }

    public function afterCreating(callable|\Closure $callback = null): static
    {
        return parent::afterCreating(function ($team) use ($callback) {
            $schemaName = $team->schema_name;

            if (!empty($schemaName)) {
                $this->createSchema($schemaName);
                $this->setSchema($schemaName);
                $this->runMigrations($schemaName);
            }
        });
    }
}
