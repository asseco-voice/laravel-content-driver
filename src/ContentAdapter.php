<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Responses\Document;
use Carbon\Carbon;
use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class ContentAdapter extends AbstractAdapter
{
    protected ContentClient $client;
    protected ExtensionMimeTypeDetector $mimeTypeDetector;

    public function __construct(ContentClient $client, string $prefix = '')
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
        $this->mimeTypeDetector = new ExtensionMimeTypeDetector();
    }

    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  Config  $config
     * @return Document
     *
     * @throws Exception
     */
    public function write($path, $contents, Config $config): Document
    {
        $overwrite = $this->fileExists($path);

        return $this->client->upload($path, $contents, $overwrite);
    }

    /**
     * @param  string  $path
     * @param  resource  $resource
     * @param  Config  $config
     * @return Document
     *
     * @throws Exception
     */
    public function writeStream($path, $resource, Config $config): Document
    {
        $overwrite = $this->fileExists($path);

        return $this->client->uploadStream($path, $resource, $overwrite);
    }

    /**
     * Update a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  Config  $config  Config object
     * @return Document
     *
     * @throws Exception
     */
    public function update($path, $contents, Config $config): Document
    {
        return $this->client->upload($path, $contents, true);
    }

    /**
     * Update a file using a stream.
     *
     * @param  string  $path
     * @param  resource  $resource
     * @param  Config  $config  Config object
     * @return Document
     *
     * @throws Exception
     */
    public function updateStream($path, $resource, Config $config): Document
    {
        return $this->client->uploadStream($path, $resource, true);
    }

    /**
     * Update a file.
     *
     * @param $path
     * @param  resource  $resource
     * @return Document
     *
     * @throws Exception
     */
    public function put($path, $resource): Document
    {
        return $this->client->upload($path, $resource);
    }

    /**
     * Update a file.
     *
     * @param $path
     * @param  resource  $resource
     * @return Document
     *
     * @throws Exception
     */
    public function putStream($path, $resource): Document
    {
        return $this->client->uploadStream($path, $resource);
    }

    /**
     * @param  string  $path
     * @return array
     *
     * @throws Exception
     */
    public function read($path)
    {
        return ['contents' => $this->client->readRaw($path)];
    }

    /**
     * @param  string  $path
     * @return false|resource
     *
     * @throws Exception
     */
    public function readStream($path)
    {
        $resource = $this->client->readStream($path);

        if ($resource === null) {
            throw new Exception('Unable to read file stream from: ' . $path . 'Empty content');
        }

        return $resource;
    }

    /**
     * @param  string  $path
     * @return bool
     *
     * @throws Exception
     */
    public function has($path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * @param  string  $path
     * @return bool
     *
     * @throws Exception
     */
    public function delete($path): bool
    {
        return $this->client->delete($path);
    }

    /**
     * @param  string  $path
     * @return array|false|string
     *
     * @throws Exception
     */
    public function readAndDelete(string $path)
    {
        $path = $this->applyPathPrefix($path);

        $contents = $this->read($path);

        if ($contents === false) {
            return false;
        }

        $this->client->delete($path);

        return $contents;
    }

    /**
     * Rename a file.
     *
     * @param  string  $path
     * @param  string  $newpath
     * @return bool
     *
     * @throws Exception
     */
    public function rename($path, $newpath): bool
    {
        return $this->client->document->moveFile($path, $newpath);
    }

    /**
     * Copy a file.
     *
     * @param  string  $path
     * @param  string  $newpath
     * @return Document
     *
     * @throws Exception
     */
    public function copy($path, $newpath): Document
    {
        $contents = $this->client->readRaw($path);

        return $this->client->upload($newpath, $contents, true);
    }

    public function getTimestamp($path)
    {
        $path = $this->applyPathPrefix($path);
        $response = $this->client->document->metadataByPath($path);

        return ['timestamp' => Carbon::createFromFormat("Y-m-d\TH:i:s.uO", $response->changedOn)->getTimestamp()];
    }

    /**
     * @param  string  $path
     * @return array
     *
     * @throws Exception
     */
    public function getMimetype($path): array
    {
        $path = $this->applyPathPrefix($path);
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            throw new Exception('Unable to retrieve Metadata mimeType: ' . $path);
        }

        return ['mimetype' => $mimeType];
    }

    /**
     * @param  string  $path
     * @return array
     *
     * @throws Exception
     */
    public function getSize($path): array
    {
        $path = $this->applyPathPrefix($path);
        $meta = $this->client->document->metadataByPath($path);

        return ['size' => $meta->sizeInBytes];
    }

    /**
     * Create a directory.
     *
     * @param  string  $dirname  directory name
     * @param  Config  $config
     * @return bool
     *
     * @throws Exception
     */
    public function createDir($dirname, Config $config): bool
    {
        return $this->client->folder->recursiveCreateFolder($dirname);
    }

    /**
     * Delete a directory.
     *
     * @param  string  $dirname
     * @return bool
     *
     * @throws Exception
     */
    public function deleteDir($dirname): bool
    {
        return $this->client->folder->delete($dirname);
    }

    /**
     * @param  string  $path
     * @param  mixed  $visibility
     *
     * @throws Exception
     */
    public function setVisibility($path, $visibility): void
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param  string  $path
     *
     * @throws Exception
     */
    public function visibility(string $path)
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param  string  $path
     * @return false
     *
     * @throws Exception
     */
    public function getVisibility($path): bool
    {
        throw new Exception('Unable to set visibility : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param  string  $directory
     * @param  bool  $recursive
     * @return array
     *
     * @throws Exception
     */
    public function listContents($directory = '', $recursive = false): array
    {
        return $this->client->tree($directory, $recursive);
    }

    public function getMetadata($path)
    {
        $path = $this->applyPathPrefix($path);

        if (substr($path, -1) === '/') {
            return $this->client->folder->metadataByPath($path);
        }

        return $this->client->document->metadataByPath($path);
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
            if ($e instanceof Exception && $e->getCode() == 404) {
                return false;
            }

            throw new Exception('Unable to check file existence for: ' . $path);
        }

        return true;
    }
}
