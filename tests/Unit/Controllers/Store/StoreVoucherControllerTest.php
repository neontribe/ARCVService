<?php

namespace Tests\Unit\Controllers\Store;

use App\Centre;
use App\CentreUser;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Session;
use Storage;
use Tests\StoreTestCase;
use URL;
use ZipArchive;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class StoreVoucherControllerTest extends StoreTestCase
{
    /**
     * Some additional coverage would be nice...
     * TODO : Test creation of the Master Voucher Log Report
     * TODO : Test that decryption failing mid-stream results in a response that is well-marked as failed
     * ...although problems here would soon be spotted by the single person who uses this feature directly.
     */

    use DatabaseMigrations;

    /** @var Centre $centre */
    private $centre;

    /** @var CentreUser $centreUser */
    private $centreUser;

    /** @var FilesystemAdapter $disk */
    private $disk;

    /** @var string $archiveName */
    private $archiveName;

    /** @var string $dashboard_route */
    private $dashboard_route;

    /** @var string $export_route */
    private $export_route;

    public function setUp(): void
    {
        parent::setUp();

        // Set routes
        $this->dashboard_route = URL::route('store.dashboard');
        $this->export_route = URL::route('store.vouchers.mvl.export');

        // Set archive details
        $this->disk = Storage::disk(config('arc.mvl_disk'));
        $this->archiveName = config('arc.mvl_filename');

        // Remove any file before we start
        if ($this->disk->exists($this->archiveName)) {
            $this->disk->delete($this>$this->archiveName);
        }

        // Set up a Centre
        $this->centre = factory(Centre::class)->create();

        // Create an FM User
        $this->centreUser = factory(CentreUser::class, 'FMUser')->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Remove any file to start with
        if ($this->disk->exists($this->archiveName)) {
            $this->disk->delete($this->archiveName);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
        // Remove any file left around
        if ($this->disk->exists($this->archiveName)) {
            $this->disk->delete($this->archiveName);
        }
    }

    /**
     * @test
     *
     * @throws \ZipStream\Exception\OverflowException
     */
    public function testItCanDecryptAStoredZip()
    {
        // Some meaningful content.
        $sourceContent = <<<EOD
Gentle - a Haiku
by Farthing
Breezy datacentre
A modest, sharp gentle freeze
by the tornado
[https://www.poem-generator.org.uk/?i=m4xaa9c]
EOD;
        // Enable the secret stream protocol (ssw://). See SecretStreamWrapper for more information.
        // Registering here guarantees availability but there's probably a static place to put this.
        if (!in_array("ssw", stream_get_wrappers())) {
            stream_wrapper_register("ssw", "App\Wrappers\SecretStreamWrapper");
        }

        // Stream a zip to a file in storage, encrypting as we write (with the secret stream wrapper).
        $storagePath = $this->disk->getAdapter()->getPathPrefix();
        $options = new Archive();
        $output = fopen('ssw://' . $storagePath . '/' . $this->archiveName, 'w');
        $options->setOutputStream($output);

        $za = new ZipStream(null, $options);
        $za->addFile('a.txt', $sourceContent);
        $za->addFile('b.txt', $sourceContent);
        $za->finish();
        fclose($output); // Close the stream manually, now, or it will not be ready in time for the read below.

        $this->assertTrue($this->disk->exists($this->archiveName));

        // Fetch the route; should return a zip that may be streamed.
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($this->export_route)
            ->response
        ;

        // Get our response's content, using the more complicated approach required by `StreamedResponse`, as file
        // content is gradually streamed as opposed to sent instantly.
        // https://stackoverflow.com/a/18277789
        ob_start(); // capture streamed response in output buffer
        $response->sendContent(); // complete streaming
        $content = ob_get_contents(); // capture streamed output
        ob_end_clean(); // turn off and empty output buffer

        // Test the content.
        // Save it to a temp file first, because ZipArchive is dumb about files, rather than streams.
        $fp = tmpfile();
        fwrite($fp, $content);
        $stream = stream_get_meta_data($fp);
        $tmpFilename = $stream['uri'];

        // Interpret the response as a ZIP. If decryption failed, something will go wrong soon.
        $zip = new ZipArchive();
        $zip->open($tmpFilename);

        // iterate over the files inside.
        $numFiles = $zip->numFiles;
        $this->assertEquals(2, $numFiles);

        for ($i = 0; $i < $numFiles; $i++) {
            // Get the file's name
            $filename = $zip->getNameIndex($i);

            // Check it's one of ours
            $this->assertContains($filename, ["a.txt", "b.txt"]);

            // Get the file contents to memory.
            $fileStream = $zip->getStream($filename);
            $fileContent = '';
            while (!feof($fileStream)) {
                $fileContent .= fread($fileStream, 8192);
            }
            // Shut that off
            fclose($fileStream);

            // Confirm the file has the contents we expect.
            $this->assertEquals($sourceContent, $fileContent);
        }

        // Close the ZipArchive operation.
        $zip->close();

        // Close and delete the temp file.
        fclose($fp);

        // See we ended up in the right route
        $this->seePageIs($this->export_route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testItRedirectsToDashboardWhenNoStoredZip()
    {
        // Setup removes the report file.
        // Get the response when we go to the page.
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($this->export_route)
        ;

        // Dig out errors from Session
        $response->seeInSession('error_message');
        $error = Session::get("error_message");

        // Check our specific message is present
        $this->assertEquals(
            "Sorry, couldn't find a current export. Please check the exporter ran on schedule",
            $error
        );

        // See it's a redirect
        $this->seeStatusCode(302);

        // we follow that to the dashboard page;
        $this->followRedirects()
            ->seePageIs($this->dashboard_route)
            ->assertResponseStatus(200)
        ;
    }

    /** @test */
    public function testItRedirectsToDashboardWhenEmptyZip()
    {
        // Make a file that isn't a zip file.
        $this->disk->put($this->archiveName, "");

        // Get the response when we go to the page.
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($this->dashboard_route)
            ->get($this->export_route)
        ;

        // Dig out errors from Session
        $response->seeInSession('error_message');
        $error = Session::get("error_message");

        // Check our specific message is present
        $this->assertEquals(
            "Sorry, the export file was unreadable. Please contact support.",
            $error
        );

        // See it's a redirect
        $this->seeStatusCode(302);

        // we follow that to the dashboard page;
        $this->followRedirects()
            ->seePageIs($this->dashboard_route)
            ->assertResponseStatus(200)
        ;
    }
}