<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Responses\Document;
use Exception;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class ContentAdapter implements FilesystemAdapter
{
    protected ContentClient $client;
    protected PathPrefixer $prefixer;
    protected ExtensionMimeTypeDetector $mimeTypeDetector;

    public function __construct(ContentClient $client, string $prefix = '')
    {
        $this->client = $client;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = new ExtensionMimeTypeDetector();
    }

    /**
     * @param  string  $path
     * @return bool
     *
     * @throws Exception
     */
    public function fileExists(string $path): bool
    {
        try {
            $path = $this->applyPathPrefix($path);
            $this->client->document->metadataByPath($path);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                return false;
            }

            throw new Exception('Unable to check file existence for: ' . $path . ' Exception:' . $e->getMessage());
        }

        return true;
    }

    /**
     * @param  string  $path
     * @return bool
     *
     * @throws Exception
     */
    public function directoryExists(string $path): bool
    {
        // TODO: check this and modify
        return $this->fileExists($path);
    }

    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  Config  $config
     * @return void
     *
     * @throws Exception
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->applyPathPrefix($path);
        $overwrite = $this->fileExists($path);

        $this->client->upload($path, $contents, $overwrite);
    }

    /**
     * @param  string  $path
     * @param $contents
     * @param  Config  $config
     * @return void
     *
     * @throws Exception
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $path = $this->applyPathPrefix($path);
        $overwrite = $this->fileExists($path);

        $this->client->uploadStream($path, $contents, $overwrite);
    }

    /**
     * @param  string  $path
     * @return string
     *
     * @throws Exception
     */
    public function read(string $path): string
    {
        $path = $this->applyPathPrefix($path);

        return $this->client->readRaw($path);
    }

    /**
     * @param  string  $path
     * @return false|resource
     *
     * @throws Exception
     */
    public function readStream(string $path)
    {
        $path = $this->applyPathPrefix($path);
        $resource = $this->client->readStream($path);

        if ($resource === null) {
            throw new Exception('Unable to read file stream from: ' . $path . 'Empty content');
        }

        return $resource;
    }

    /**
     * @param  string  $path
     * @return void
     *
     * @throws Exception
     */
    public function delete(string $path): void
    {
        $path = $this->applyPathPrefix($path);

        $this->client->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $path = $this->applyPathPrefix($path);

        $this->client->folder->delete($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $path = $this->applyPathPrefix($path);

        $this->client->folder->recursiveCreateFolder($path);
    }

    /**
     * @param  string  $path
     * @param  string  $visibility
     * @return void
     *
     * @throws Exception
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param  string  $path
     * @return FileAttributes
     *
     * @throws Exception
     */
    public function visibility(string $path): FileAttributes
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param  string  $path
     * @return FileAttributes
     *
     * @throws Exception
     */
    public function mimeType(string $path): FileAttributes
    {
        $path = $this->applyPathPrefix($path);

        /** @var Document $document */
        $document = $this->client->document->metadataByPath($path);

        return new FileAttributes(
            $path,
            $document->sizeInBytes ?: null,
            null,
            null,
            $this->mimeTypeDetector->detectMimeTypeFromPath($path)
        );
    }

    /**
     * @param  string  $path
     * @return FileAttributes
     *
     * @throws Exception
     */
    public function lastModified(string $path): FileAttributes
    {
        throw new Exception('Implement this');
    }

    /**
     * @param  string  $path
     * @return FileAttributes
     *
     * @throws Exception
     */
    public function fileSize(string $path): FileAttributes
    {
        return $this->mimeType($path);
    }

    /**
     * @param  string  $path
     * @param  bool  $deep
     * @return iterable
     *
     * @throws Exception
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return $this->client->tree($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $source = $this->applyPathPrefix($source);
        $destination = $this->applyPathPrefix($destination);

        $this->client->document->moveFile($source, $destination);
    }

    /**
     * @param  string  $source
     * @param  string  $destination
     * @param  Config  $config
     * @return void
     *
     * @throws Exception
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $source = $this->applyPathPrefix($source);
        $destination = $this->applyPathPrefix($destination);

        $contents = $this->client->readRaw($source);

        $this->client->upload($destination, $contents, true);
    }

    protected function applyPathPrefix($path): string
    {
        return '/' . trim($this->prefixer->prefixPath($path), '/');
    }
}
