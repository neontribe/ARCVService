<?php

namespace Database\Seeders;

use App\Centre;
use Illuminate\Database\Seeder;

class DeletedCentreSeeder extends Seeder
{
    public function run(): void
    {
        factory(Centre::class)->states('deleted')->create([
            'name' => 'Deleted Centre',
            'sponsor_id' => 1,
        ]);
    }
}
