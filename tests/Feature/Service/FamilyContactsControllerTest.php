<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Carer;
use App\Centre;
use App\Family;
use App\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FamilyContactsControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-03-15 09:30:00');
        $this->admin = factory(AdminUser::class)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Creates a fully-related Carer — the happy-path fixture.
     * Override any attribute by passing it in $carerAttributes.
     */
    private function createQualifyingCarer(array $carerAttributes = []): Carer
    {
        $sponsor = factory(Sponsor::class)->create(['name' => 'Test Area']);
        $centre = factory(Centre::class)->create([
            'name' => 'Test Centre',
            'sponsor_id' => $sponsor->id,
        ]);
        $family = factory(Family::class)->create(['initial_centre_id' => $centre->id]);

        return factory(Carer::class)->create(array_merge([
            'family_id' => $family->id,
            'name' => 'Jane Doe',
            'emailsecret' => 'jane@example.com',
            'telnosecret' => '07700900000',
        ], $carerAttributes));
    }

    private function makeRequest(): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get(route('data.families.contacts.download'));
    }

    public function testReturns200(): void
    {
        $this->makeRequest()->assertOk();
    }

    public function testContentTypeIsCsv(): void
    {
        $this->makeRequest()->assertHeaderContains('Content-Type', 'text/csv');
    }

    public function testCacheControlIsNoCache(): void
    {
        $this->makeRequest()->assertHeaderContains('Cache-Control', 'no-cache');
    }

    public function testFilenameContainsFormattedTimestamp(): void
    {
        $disposition = $this->makeRequest()->headers->get('Content-Disposition');

        $this->assertStringContainsString('carers-export_20240315093000.csv', $disposition);
    }

    public function testFilenameChangesWithCurrentTime(): void
    {
        Carbon::setTestNow('2099-12-31 23:59:59');

        $disposition = $this->makeRequest()->headers->get('Content-Disposition');

        $this->assertStringContainsString('carers-export_20991231235959.csv', $disposition);
    }

    public function testFirstRowIsHeader(): void
    {
        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertSame(['Rvid', 'Name', 'Email', 'Telno', 'Centre', 'Area'], $rows[0]);
    }

    /** @return array<int, array<int, string>> */
    private function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", trim($content)));
        return array_map('str_getcsv', array_values($lines));
    }

    public function testEmptyResultSetReturnsHeaderOnly(): void
    {
        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(1, $rows);
    }

    public function testExcludesCarerWithNullEmailsecret(): void
    {
        $this->createQualifyingCarer(['emailsecret' => null]);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(1, $rows, 'Expected header row only — carer should be excluded.');
    }

    public function testExcludesCarerWithNullTelnosecret(): void
    {
        $this->createQualifyingCarer(['telnosecret' => null]);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(1, $rows, 'Expected header row only — carer should be excluded.');
    }

    public function testExcludesCarerWithBothSecretsNull(): void
    {
        $this->createQualifyingCarer(['emailsecret' => null, 'telnosecret' => null]);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(1, $rows);
    }

    public function testIncludesCarerWithBothSecretsPresent(): void
    {
        $this->createQualifyingCarer();

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(2, $rows, 'Expected header row + 1 data row.');
    }

    public function testDataRowMapsAllColumnsCorrectly(): void
    {
        $carer = $this->createQualifyingCarer([
            'name' => 'Jane Doe',
            'emailsecret' => 'jane@example.com',
            'telnosecret' => '07700900000',
        ]);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());
        $dataRow = $rows[1];

        $this->assertSame((string)$carer->family->Rvid, $dataRow[0], 'Rvid');
        $this->assertSame('Jane Doe', $dataRow[1], 'Name');
        $this->assertSame('jane@example.com', $dataRow[2], 'Email');
        $this->assertSame('07700900000', $dataRow[3], 'Telno');
        $this->assertSame('Test Centre', $dataRow[4], 'Centre');
        $this->assertSame('Test Area', $dataRow[5], 'Area');
    }

    public function testMultipleQualifyingCarersAllAppear(): void
    {
        $this->createQualifyingCarer(['name' => 'Carer One']);
        $this->createQualifyingCarer(['name' => 'Carer Two']);
        $this->createQualifyingCarer(['name' => 'Carer Three']);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());

        $this->assertCount(4, $rows, 'Expected header row + 3 data rows.');
    }

    public function testQualifyingAndNonQualifyingCarersMixed(): void
    {
        $this->createQualifyingCarer(['name' => 'Included']);
        $this->createQualifyingCarer(['name' => 'Excluded — no email', 'emailsecret' => null]);
        $this->createQualifyingCarer(['name' => 'Excluded — no telno', 'telnosecret' => null]);

        $rows = $this->parseCsv($this->makeRequest()->streamedContent());
        $dataRows = array_slice($rows, 1);

        $this->assertCount(1, $dataRows);
        $this->assertSame('Included', $dataRows[0][1]);
    }
}
