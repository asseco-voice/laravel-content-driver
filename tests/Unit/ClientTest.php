<?php

namespace Asseco\ContentFileStorageDriver\Tests;

use Asseco\ContentFileStorageDriver\ContentClient;
use Exception;
use Illuminate\Http\UploadedFile;

class ClientTest extends TestCase
{
    /**
     * @var ContentClient
     */
    protected ContentClient $client;

    /**
     * random_id.
     */
    private int $testCaseId;
    protected string $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->getClientInstance();
        $this->testCaseId = rand(0, 999999);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(ContentClient::class, $this->getClientInstance());
    }

    /**
     * @test
     * @throws Exception
     */
    public function is_folder_exists()
    {
        $this->assertTrue($this->client->folderExist('/'));
        $this->assertTrue($this->client->folderExist('/unittest'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function is_folder_unittest_exists_delete_all()
    {
        if ($this->client->folderExist('/unittest1')) {
            $this->client->deleteFolders('/unittest1', true);
        }
        $this->client->createFolder('unittest1', '/');
        $metadata = $this->client->getDirectoryMetadata('/');
        $this->assertObjectHasAttribute('id', $metadata);
        // $this->assertObjectHasAttribute('created-by', $metadata);
        // $this->assertObjectHasAttribute('changed-on', $metadata);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_create_a_file_from_upload()
    {
        $filepath = __DIR__ . '/assets/testing.txt';
        $upload = new UploadedFile($filepath, 'testing_' . $this->testCaseId . '.txt', 'plain/text');
        $contents = $this->client->upload('/unittest1/' . $this->testCaseId, $upload, 'Created file', '1', true);

        $this->assertTrue('testing_' . $this->testCaseId . '.txt' === $contents->name);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_can_read_new_file()
    {
        $this->withoutExceptionHandling();
        $metadata = $this->client->getDocumentMetadata('/unittest1/testing_' . $this->testCaseId . '.txt');

        if (!empty($metadata->name)) {
            $data = $this->client->getFile('/unittest1/testing_' . $this->testCaseId . '.txt')->getBody()->getContents();
            $this->assertStringStartsWith('File for testing file streams', $data);

            $data = stream_get_contents($this->client->readRaw('/unittest1/testing_' . $this->testCaseId . '.txt'));
            $this->assertStringStartsWith('File for testing file streams', $data);
        }

        $this->assertEmpty($metadata->name);
    }

    /**
     * @throws Exception
     */
    public function it_can_delete_a_file()
    {
        $this->withoutExceptionHandling();
        $this->client->delete('/unittest1/testing_' . $this->testCaseId . '.txt');
    }
}
