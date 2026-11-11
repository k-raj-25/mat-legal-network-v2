<?php

namespace Database\Factories;

use App\Enums\LawyerStatus;
use App\Models\City;
use App\Models\Country;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LawyerProfile>
 */
class LawyerProfileFactory extends Factory
{
    protected $model = LawyerProfile::class;

    public function definition(): array
    {
        $city = City::inRandomOrder()->first();

        return [
            'user_id' => User::factory(),
            'full_name' => 'Adv. '.fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'designation' => 'Advocate',
            'firm_name' => fake()->optional()->company(),
            'practice_since' => fake()->numberBetween(1985, (int) now()->year),
            'education' => 'LL.B.',
            'bio' => fake()->paragraph(),
            'consultation_fee' => fake()->optional()->randomElement([500, 1000, 1500, 2000, 3000]),
            'offers_online_consultation' => fake()->boolean(),
            'country_id' => Country::where('iso2', 'IN')->value('id'),
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'phone' => '9'.fake()->numerify('#########'),
            'office_address' => fake()->address(),
            'pincode' => fake()->numerify('1#####'),
            'enrolment_number' => 'D/'.fake()->unique()->numerify('####').'/'.fake()->year(),
            'bar_council' => 'Bar Council of Delhi',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (LawyerProfile $l) {
            $l->status ??= LawyerStatus::Pending;
            $l->submitted_at ??= now();
        });
    }

    public function status(LawyerStatus $status): static
    {
        return $this->afterMaking(function (LawyerProfile $l) use ($status) {
            $l->status = $status;
            if ($status === LawyerStatus::Approved) {
                $l->approved_at = now();
            }
        });
    }

    public function approved(): static
    {
        return $this->status(LawyerStatus::Approved);
    }

    public function pending(): static
    {
        return $this->status(LawyerStatus::Pending);
    }
}
