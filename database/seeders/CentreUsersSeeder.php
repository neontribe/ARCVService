<?php

namespace Database\Seeders;

use App\Centre;
use App\CentreUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentreUsersSeeder extends Seeder
{
    /**
     * Password shared by all named seed users.
     */
    private string $defaultPassword;

    public function run(): void
    {
        $this->defaultPassword = Hash::make('store_pass');

        $this->seedCcUser();
        $this->seedNamedUsers();
        $this->seedRandomUsers();
        $this->seedDeletedUsers();
        $this->seedRetiredUsers();
    }

    /**
     * CC user requires additional centre setup so is handled separately.
     */
    private function seedCcUser(): void
    {
        $user = $this->createAndAttach(
            attributes: ['name' => 'ARC CC User', 'email' => 'arc+ccuser@exmaple.com', 'role' => 'centre_user'],
            centreId: 1,
            isHome: true
        );

        // Two extra centres sharing the same sponsor, attached as non-home.
        $sponsorId = $user->centres()->first()->sponsor->id;
        $localCentres = factory(Centre::class, 2)->create(['sponsor_id' => $sponsorId]);

        $user->centres()->attach([
            $localCentres[0]->id => ['homeCentre' => false],
            $localCentres[1]->id => ['homeCentre' => false],
        ]);
    }

    /**
     * Named users with fixed centre assignments.
     * Each entry: [ state, name, email, centreId | centreName ]
     */
    private function seedNamedUsers(): void
    {
        $users = [
            ['FMUser', 'ARC FM User', 'arc+fmuser@exmaple.com', 1],
            ['FMUser', 'ARC fmuser2', 'arc+fmuser2@exmaple.com', 2],
            ['', 'prescribing user', 'arc+spuser@exmaple.com', 'Prescribing Centre'],
            ['', 'Scottish user', 'arc+scuser@exmaple.com', 8],
            ['', 'Southwark user', 'arc+swuser@exmaple.com', 6],
            ['', 'Tower Hamlet SP user', 'arc+thuser@exmaple.com', 10],
            ['', 'Lambeth SP user', 'arc+lambethuser@exmaple.com', 11],
        ];

        foreach ($users as [$state, $name, $email, $centre]) {
            $centreId = is_int($centre)
                ? $centre
                : Centre::where('name', $centre)->first()->id;

            $this->createAndAttach(
                attributes: ['name' => $name, 'email' => $email],
                centreId: $centreId,
                state: $state
            );
        }
    }

    /**
     * Four faked users each attached to a random existing centre.
     */
    private function seedRandomUsers(): void
    {
        factory(CentreUser::class, 4)
            ->create()
            ->each(function (CentreUser $centreUser) {
                $centre = Centre::inRandomOrder()->first()
                    ?? factory(Centre::class)->create();

                $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
            });
    }

    /**
     * Two soft-deleted users.
     */
    private function seedDeletedUsers(): void
    {
        factory(CentreUser::class, 2)->create(['deleted_at' => now()]);
    }

    /**
     * Two retired users, each attached to a random centre before retirement.
     *
     * Centre relations are preserved on the underlying row after retirement.
     * Retirement is called directly rather than via the factory state because
     * the centre must be attached first — retire() must run last.
     */
    private function seedRetiredUsers(): void
    {
        factory(CentreUser::class, 2)
            ->create()
            ->each(function (CentreUser $centreUser) {
                $centre = Centre::inRandomOrder()->first()
                    ?? factory(Centre::class)->create();

                $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
                $centreUser->retire();
            });
    }

    /**
     * Create a CentreUser and attach them to a centre in one step.
     */
    private function createAndAttach(
        array $attributes,
        int $centreId,
        bool $isHome = true,
        string $state = ''
    ): CentreUser {
        $attributes['password'] ??= $this->defaultPassword;

        $builder = factory(CentreUser::class);

        if ($state !== '') {
            $builder = $builder->state($state);
        }

        $user = $builder->create($attributes);
        $user->centres()->attach($centreId, ['homeCentre' => $isHome]);

        return $user;
    }
}
