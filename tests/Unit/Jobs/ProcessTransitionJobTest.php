<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessTransitionJob;
use App\Trader;
use App\User;
use App\Voucher;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Imtigger\LaravelJobStatus\JobStatus;
use Tests\CreatesApplication;

class ProcessTransitionJobTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesApplication;

    private Generator $faker;
    private Trader $trader;
    private array $voucherCodes;
    private string $transition;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create(config('app.locale'));
        $this->trader = factory(Trader::class)->create();
        $this->user = factory(User::class)->create();
        $this->transition = "";

        $now = Carbon::now()->subMonths(2);

        $this->voucherCodes = factory(Voucher::class, 100)->state('printed')->create([
            'trader_id' => $this->trader,
            'created_at' => $now,
            'updated_at' => $now,
            'currentstate' => 'printed'
        ])->pluck("id")->toArray();
    }

    public function testRetryingHandler()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::retryingHandler($jobStatus);
        $this->assertEquals(200, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("retry-after", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }

    public function testHandle()
    {
        Auth::shouldReceive('check')->once()->andreturn(false);
        Auth::shouldReceive('login')->once();
        Auth::shouldReceive('user')->once()->andreturn($this->user);
        Auth::shouldReceive('logout')->once();

        Cache::shouldReceive('put')->once();

        $ptj = new ProcessTransitionJob($this->trader, $this->voucherCodes, $this->transition, $this->user->id);
        $ptj->handle();
    }

    public function testQueuedHandler()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::queuedHandler($jobStatus);
        $this->assertEquals(200, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("retry-after", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }

    public function testExecutingHandler()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::executingHandler($jobStatus);
        $this->assertEquals(200, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("retry-after", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }

    public function testFailedHandler()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::failedHandler($jobStatus);
        $this->assertEquals(200, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("retry-after", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }

    public function testMonitor()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::monitor($jobStatus);
        $this->assertEquals(202, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("retry-after", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }

    public function testFinishedHandler()
    {
        $jobStatus = JobStatus::create([
            "job_id" => 1,
            "type" => "job type",
            "queue" => "some queue",
            "input" => "some input",
            "output" => "some output",
        ]);
        $jobStatus->input = ['id' => '123'];

        $result = ProcessTransitionJob::finishedHandler($jobStatus);
        $this->assertEquals(202, $result->status());
        $json = json_decode($result->content(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("location", $json);
        $this->assertArrayHasKey("id", $json);
        $this->assertArrayHasKey("status", $json);
    }
}
