<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Responses\Document;
use DateTime;
use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

/**
 * Class ContentAdapter.
 */
class ContentAdapter extends AbstractAdapter
{
    /**
     * @var ContentClient
     */
    protected ContentClient $client;

    /**
     * @var ExtensionMimeTypeDetector
     */
    protected ExtensionMimeTypeDetector $mimeTypeDetector;

    /**
     * ContentAdapter constructor.
     *
     * @param  ContentClient  $client
     * @param string $prefix
     */
    public function __construct(ContentClient $client, string $prefix = '')
    {
        $this->setClient($client);
        $this->setPathPrefix($prefix);
        $this->mimeTypeDetector = new ExtensionMimeTypeDetector();
    }

    /**
     * @param string $path
     *
     * @return bool
     * @throws Exception
     */
    public function fileExists(string $path): bool
    {
        try {
            $this->client->getDocumentMetadata($path);
        } catch (Exception $e) {
            if ($e instanceof Exception && $e->getCode() == 404) {
                return false;
            }
            throw new Exception('Unable to check file existence for: ' . $path);
        }

        return true;
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return Document
     * @throws Exception
     */
    public function write($path, $contents, Config $config): Document
    {
        try {
            $override = $this->fileExists($path);

            return $this->client->upload($path, $contents, $override);
        } catch (Exception $e) {
            throw new Exception('Unable to write file to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     *
     * @return Document
     * @throws Exception
     */
    public function writeStream($path, $resource, Config $config): Document
    {
        try {
            $override = $this->fileExists($path);

            return $this->client->uploadStream($path, $resource, $override);
        } catch (Exception $e) {
            throw new Exception('Unable to write file to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return Document
     * @throws Exception
     */
    public function update($path, $contents, Config $config): Document
    {
        try {
            return $this->client->upload($path, $contents, null, true);
        } catch (Exception $e) {
            throw new Exception('Unable to update file to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return Document
     * @throws Exception
     */
    public function updateStream($path, $resource, Config $config): Document
    {
        try {
            return $this->client->upload($path, $resource, null, true);
        } catch (Exception $e) {
            throw new Exception('Unable to update stream to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Update a file.
     *
     * @param $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return Document
     * @throws Exception
     */
    public function put($path, $resource, Config $config): Document
    {
        try {
            return $this->client->upload($path, $resource, null, true);
        } catch (Exception $e) {
            throw new Exception('Unable to update stream to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Update a file.
     *
     * @param $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return Document
     * @throws Exception
     */
    public function putStream($path, $resource, Config $config): Document
    {
        try {
            return $this->client->upload($path, $resource, null, true);
        } catch (Exception $e) {
            throw new Exception('Unable to update stream to: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws Exception
     */
    public function read($path): string
    {
        try {
            $object = $this->client->readRaw($path);
            if ($object === false) {
                return false;
            }

            return $object;
        } catch (Exception $e) {
            throw new Exception('Unable to read file from: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function readStream($path)
    {
        try {
            if (null === ($resource = $this->client->readStream($path))) {
                throw new Exception('Unable to read file stream from: ' . $path . 'Empty content');
            }

            return $resource;
        } catch (Exception $e) {
            throw new Exception('Unable to read file from: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return bool
     * @throws Exception
     */
    public function has($path): bool
    {
        return $this->fileExists($path);
    }

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function delete($path): bool
    {
        try {
            return $this->client->delete($path);
        } catch (Exception $e) {
            throw new Exception('Unable to delete file at: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws Exception
     */
    public function readAndDelete(string $path): string
    {
        $path = $this->applyPathPrefix($path);
        try {
            $contents = $this->read($path);

            if ($contents === false) {
                return false;
            }

            $this->client->delete($path);

            return $contents;
        } catch (Exception $e) {
            throw new Exception('Unable to delete file at: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     * @throws Exception
     */
    public function rename($path, $newpath): bool
    {
        try {
            return $this->client->moveFile($path, $newpath);
        } catch (Exception $e) {
            throw new Exception('Unable to delete file at: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     * @throws Exception
     */
    public function copy($path, $newpath): bool
    {
        try {
            $contents = $this->client->readRaw($path);

            return $this->client->upload($newpath, $contents, 'copy file', 'some id', true);
        } catch (Exception $e) {
            throw new Exception('Unable to copy file from: ' . $path . ' to: ' . $newpath . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @throws Exception
     */
    public function getTimestamp($path)
    {
        $path = $this->applyPathPrefix($path);
        try {
            $response = $this->client->getDocumentMetadata($path);

            return DateTime::createFromFormat("Y-m-d\TH:i:s.uO", $response->changed_on);
        } catch (Exception $e) {
            throw new Exception('Unable get getTimestamp: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function getMimetype($path): string
    {
        $path = $this->applyPathPrefix($path);
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            throw new Exception('Unable to retrieve Metadata mimeType: ' . $path);
        }

        return $mimeType;
    }

    /**
     * @param string $path
     *
     * @return int
     * @throws Exception
     */
    public function getSize($path): int
    {
        $path = $this->applyPathPrefix($path);
        try {
            $meta = $this->client->getDocumentMetadata($path);

            return $meta->sizeInBytes;
        } catch (Exception $e) {
            throw new Exception('Unable to retrieve file size: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return bool
     * @throws Exception
     */
    public function createDir($dirname, Config $config): bool
    {
        $path = $this->applyPathPrefix($dirname);

        try {
            return $this->client->createFolder($path);
        } catch (Exception $e) {
            throw new Exception('Unable to create new directory to: ' . $dirname . ' ' . $e->getMessage());
        }
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     * @throws Exception
     */
    public function deleteDir($dirname): bool
    {
        try {
            return $this->client->deleteFolders($dirname, true);
        } catch (Exception $e) {
            throw new Exception('Unable to delete directory from: ' . $dirname . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param mixed $visibility
     *
     * @throws Exception
     */
    public function setVisibility($path, $visibility): void
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function visibility(string $path)
    {
        throw new Exception('Unable to set visibility  : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $path
     * @return false
     * @throws Exception
     */
    public function getVisibility($path): bool
    {
        throw new Exception('Unable to set visibility : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $directory
     * @param bool $recursive
     *
     * @throws Exception
     */
    public function listContents($directory = '', $recursive = false)
    {
        try {
            return $this->client->tree($directory, $recursive);
        } catch (Exception $e) {
            throw new Exception('Unable to retrieve from: ' . $directory . ' ' . $e->getMessage());
        }
    }

    /**
     * @return ContentClient
     */
    public function getClient(): ContentClient
    {
        return $this->client;
    }

    /**
     * @param  ContentClient  $client
     */
    public function setClient(ContentClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $path
     * @return array
     * @throws Exception
     */
    public function getMetadata($path): array
    {
        $path = $this->applyPathPrefix($path);

        try {
            if (substr($path, -1) === '/') {
                return $this->client->getDirectoryMetadata($path);
            } else {
                return $this->client->getDocumentMetadata($path);
            }
        } catch (Exception $e) {
            throw new Exception('getMetadata from: ' . $path . ' ' . $e->getMessage());
        }
    }
}
