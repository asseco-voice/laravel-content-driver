<?php

declare(strict_types=1);

namespace Asseco\ContentFileStorageDriver\Tests;

use Asseco\ContentFileStorageDriver\ContentAdapter;
use Asseco\ContentFileStorageDriver\ContentClient;
use Exception;
use League\Flysystem\Config;

class ContentAdapterTest extends TestCase
{
    /**
     * @var ContentAdapter
     */
    protected ContentAdapter $contentAdapter;

    public function setUp(): void
    {
        $this->contentAdapter = $this->getAdapterInstance();
    }

//    /**
//     * @test
//     */
//    public function it_can_be_instantiated()
//    {
//        $this->withoutExceptionHandling();
//        $this->assertInstanceOf(ContentAdapter::class, $this->contentAdapter);
//    }
//
//    /**
//     * @test
//     */
//    public function it_can_retrieve_client_instance()
//    {
//        $this->assertInstanceOf(ContentClient::class, $this->contentAdapter->getClient());
//    }
//
//    /**
//     * @test
//     * @throws Exception
//     */
//    public function it_can_write_a_file_stream()
//    {
//        $stream = fopen(__DIR__ . '/assets/testing.txt', 'r+');
//        $this->contentAdapter->writeStream('README.md', $stream, new Config());
//        fclose($stream);
//
//        $this->assertTrue($this->contentAdapter->fileExists('README.md'));
//        $this->assertEquals('File for testing file streams', $this->contentAdapter->read('README.md'));
//
//        $this->contentAdapter->delete('README.md');
//    }
//
//    /**
//     * @test
//     * @throws Exception
//     */
//    public function it_can_read_a_file()
//    {
//        $response = $this->contentAdapter->read('README.md');
//
//        $this->assertStringStartsWith('File for testing file streams', $response);
//    }
//
//    /**
//     * @test
//     * @throws Exception
//     */
//    public function it_can_read_a_file_into_a_stream()
//    {
//        $stream = $this->contentAdapter->readStream('README.md');
//
//        $this->assertIsResource($stream);
//        $this->assertEquals(stream_get_contents($stream, -1, 0), $this->contentAdapter->read('README.md'));
//    }
//
//    /**
//     * @test
//     * @throws Exception
//     */
//    public function it_throws_when_read_failed()
//    {
//        $this->contentAdapter->read('README.md');
//    }
//
//    /**
//     * @test
//     * @throws Exception
//     */
//    public function it_can_determine_if_a_folder_has_a_file()
//    {
//        $this->assertTrue($this->contentAdapter->fileExists('/README.md'));
//
//        $this->assertFalse($this->contentAdapter->fileExists('/I_DONT_EXIST.md'));
//    }
}
