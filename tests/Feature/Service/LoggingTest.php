<?php

namespace Tests\Feature\Service;

use App\Http\Controllers\API\LoggingController;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\StoreTestCase;

class LoggingTest extends StoreTestCase
{
    private Generator $faker;

    /** @var array $postData */
    private array $postData;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->faker->seed(1234);
        $this->postData = [
            $this->faker->sha256() => LogFactory::createPayload($this->faker),
            $this->faker->sha256() => LogFactory::createPayload($this->faker),
            $this->faker->sha256() => LogFactory::createPayload($this->faker),
            $this->faker->sha256() => LogFactory::createPayload($this->faker),
        ];
    }

    /**
     * @test
     *
     * @return void
     */
    public function itLogsData()
    {
        $storage = Storage::fake('log');

        Storage::shouldReceive('disk')
            ->with('log')
            ->andReturn($storage);

        $request = new MockRequest($this->postData);
        $loggingController = new LoggingController();
        $response = $loggingController->log($request);

        $json = json_decode($response->content());
        foreach (array_keys($this->postData) as $key) {
            $this->assertTrue(in_array($key, $json));
        }
    }
}

class MockRequest extends Request
{
    public function __construct($postData)
    {
        $this->content = json_encode($postData);
    }

    public function getContent(bool $asResource = false)
    {
        return $this->content;
    }
}

class LogFactory
{
    public static function createPayload($faker): array
    {
        $data = [
            "config" => ["url" => $faker->url()],
            "status" => $faker->randomElement([200, 200, 200, 200, 200, 201, 201, 201, 302, 401, 402, 500]),
            "created" => $faker->unixTime(),
            "trader" => $faker->randomNumber(2, true),
            "data" => [],
        ];

        $range = rand(1, 10);
        for ($i = 0; $i < $range; ++$i) {
            $data["data"][] = [
                "code" => "ABC" . $faker->randomNumber(8, true),
                "updated_at" => $faker->date('d-m-y ') . " " . $faker->time(),
            ];
        }
        return $data;
    }
}
