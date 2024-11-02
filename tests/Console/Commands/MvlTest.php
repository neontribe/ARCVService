<?php

namespace Tests\Console\Commands;

use App\Console\Commands\MvlExport;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\Voucher;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesApplication;

class MvlTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesApplication;

    private Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));

        $sponsor = factory(Sponsor::class)->create(['name' => "Real Virtual Project", "shortcode" => "RVNT", 'can_tap' => true]);

        $_market = [
            'name' => "McFarlane Range",
            'location' => 'Hennigan\'s Stead',
            'sponsor_id' => $sponsor->id,
            'payment_message' => 'Please take your vouchers to the post office.'
        ];
        $market = factory(Market::class)->create($_market);

        $_trader = ['name' => "Armadillo general store", 'market_id' => $market->id];
        $trader = factory(Trader::class)->create($_trader);

        $date = Carbon::now()->subMonths(2);

        $vouchers = factory(Voucher::class, 100)->state('printed')->create([
            'trader_id' => $trader,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'printed'
        ]);
        foreach ($vouchers as $voucher) {
            $voucher->applyTransition('dispatch');
            $voucher->applyTransition('collect');
            $voucher->applyTransition('confirm');
            $voucher->applyTransition('payout');
        }
    }


    public function testExportAndProcess()
    {
        $start = Carbon::now()->subMonths(1);
        $end = Carbon::now()->addMonths(1);

        $results = $this
            ->artisan(
                sprintf(
                    "arc:mvl:export --chunk-size=20 --from=%s --to=%s",
                    $start->format("d/m/Y"),
                    $end->format("d/m/Y")
                )
            )
            ->execute();
        $this->assertEquals(0, $results);

        // This will have been written too my the previous test
        $outputDir = sprintf(
            "%s/mvl/export/%s",
            Storage::path(MvlExport::DISK),
            Carbon::now()->format("Y-m-d")
        );
        $filename = sprintf(
            "%s/vouchers.%s-to-%s.0001.arcx",
            $outputDir,
            $start->format("Ymd"),
            $end->format("Ymd")
        );
        $this->assertFileExists($filename);
        $results = $this
            ->artisan(
                sprintf(
                    "arc:mvl:process %s",
                    $filename
                )
            )
            ->execute();
        $this->assertEquals(0, $results);
    }


    public function testCryptAndCat()
    {
        $testFilename = "build/arc_test_file_" . $this->faker->randomNumber(5, true);
        $plainText = $this->faker->text(500);
        file_put_contents($testFilename, $plainText);

        $results = $this
            ->artisan("arc:mvl:encrypt $testFilename")
            ->execute();
        $this->assertEquals(0, $results);
        $this->assertFileExists($testFilename . ".enc");
        $cypherFileName = $testFilename . ".enc";
        $cypherText = file_get_contents($cypherFileName);
        $this->assertNotEquals($plainText, $cypherText);

        $results = $this
            ->artisan("arc:mvl:cat $cypherFileName")
            ->expectsOutputToContain($plainText)
            ->execute();
        $this->assertEquals(0, $results);

        unlink($testFilename);
    }

    public function testEncryptNoFile()
    {
        $results = $this
            ->artisan("arc:mvl:encrypt build/no_such_file")
            ->execute();
        $this->assertEquals(1, $results);
    }

    public function testCatNoFile()
    {
        $results = $this
            ->artisan("arc:mvl:cat build/no_such_file")
            ->execute();
        $this->assertEquals(1, $results);
    }

    public function testCatSodiumError()
    {
        $testFilename = "build/arc_test_file_" . $this->faker->randomNumber(5, true);
        file_put_contents($testFilename, "not cypher text");
        $results = $this
            ->artisan("arc:mvl:cat " . $testFilename)
            ->execute();
        $this->assertEquals(2, $results);
    }
}
