<?php

namespace Asseco\ContentFileStorageDriver;

use Exception;
#use GuzzleHttpException\GuzzleException;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ArrayShape;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util\MimeType;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use DateTime;
#use GuzzleHttpException\ClientException;
use League\Flysystem\FilesystemException;



/**
 * Class ContentAdapter
 *
 * @package Content\Flysystem
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
        $this->setClient($client) ;
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
            $this->client->read($path);
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
     * @return bool|array
     * @throws Exception|GuzzleException
     */
    public function write($path, $contents, Config $config): bool|array
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
     * @throws Exception|GuzzleException
     */
    public function writeStream($path, $resource, Config $config): void
    {
        try {
            $override = $this->fileExists($path);
            
            $this->client->uploadStream($path, $resource, $override);
        } catch (Exception $e) {
            throw new Exception('Unable to write file to: ' . $path . ' ' . $e->getMessage());
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
            return $this->client->readRaw($path);
        } catch (Exception $e) {
            throw new Exception('Unable to read file from: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function readStream($path): bool|array|null
    {
        try {
            if (null === ($resource = $this->client->readStream($path))) {
                throw new Exception('Unable to read file from: ' . $path . 'Empty content');
            }

            return $resource;
        } catch (Exception $e) {
            throw new Exception('Unable to read file from: ' . $path . ' ' . $e->getMessage());
        }
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
     * @throws Exception
     */
    public function deleteDirectory(string $path): void
    {
        $files = $this->listContents($path, false);

        foreach ($files as $file) {
            if ($file->isFile()) {
                try {
                    $this->client->delete($file->path());
                } catch (Exception $e) {
                    throw new Exception('Unable to delete file at: ' . $path . ' ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param string $path
     * @param Config $config
     *
     * @throws Exception|GuzzleException
     */
    public function createDirectory(string $path, Config $config): void
    {
        $path = rtrim($path, '/');
    
        try {
            $this->write($path, '', $config);
        } catch (Exception $e) {
            throw new Exception('Unable to create directory to: ' . $path . ' ' . $e->getMessage());
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
	    throw new Exception('UnableToSetVisibility : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $path
     *
     * @throws Exception
     */
    public function visibility(string $path)
    {
        throw new Exception('UnableToSetVisibility : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws Exception
     */
    public function mimeType(string $path): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($path);
    
        if ($mimeType === null) {
	        throw new Exception('Unable to retrieve Metadata mimeType: ' . $path);
        }
        
        return $mimeType;
    }

    /**
     * @param string $path
     *
     * @return bool|DateTime
     * @throws Exception
     */
    public function lastModified(string $path): bool|DateTime
    {
        try {
            $response = $this->client->getMetadata($path);
            return DateTime::createFromFormat("Y-m-d\TH:i:s.uO", $response[0]['create_date']);
        } catch (Exception $e) {
            throw new Exception('Unable to retrieve Metadata from: ' . $path . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $path
     *
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    public function fileSize(string $path): mixed
    {
        try {
            $meta = $this->client->read($path);
            return $meta['size'][0] ?? 0;
        } catch (Exception $e) {
            throw new Exception('Unable to retrieve Metadata sileSize: ' . $path . ' ' . $e->getMessage());
        }
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

            $tree = $this->client->tree($directory, $recursive);
            
            foreach ($tree as $folders) {
                foreach ($folders as $item) {
                    #$isDirectory = $item['type'] == 'tree';
        
                    yield $item;
                    /*
                     $isDirectory ? new DirectoryAttributes($item['path'], null, null) : new FileAttributes(
                        $item['path'],
                        $this->fileSize($item['path'])->fileSize(),
                        null,
                        $this->lastModified($item['path'])->lastModified(),
                        $this->mimeTypeDetector->detectMimeTypeFromPath($item['path'])
                    );
                    */
                }
            }

            return ;
        } catch (Exception $e) {
            throw new Exception('Unable to retrieve FileTree from: ' . $directory . ' ' . $e->getMessage());
        }
    }

    /**
     * @param string $source
     * @param string $destination
     * @param Config|null $config
     *
     * @return array
     * @throws Exception
     */
    public function move(string $source, string $destination, Config $config = null): array
    {
        try {
            return $this->client->moveFile($source, $destination);
        } catch (Exception $e) {
            throw new Exception('Unable to move file from: ' . $source . ' to: ' . $destination . ' ' . $e->getMessage());
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
            $this->client->upload($newpath, $contents,null, true);
            return true;
        } catch (Exception $e) {
            throw new Exception('Unable to copy file from: ' . $path . ' to: ' . $newpath . ' ' . $e->getMessage());
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
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws Exception
     */
    public function update($path, $contents, Config $config): bool|array
    {
        return $this->client->upload($path, $contents, null,true);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws Exception
     */
    public function updateStream($path, $resource, Config $config): bool|array
    {
        return $this->client->upload($path, $resource, null,true);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool|array
     * @throws Exception
     */
    public function rename($path, $newpath): bool|array
    {
        return $this->move($path, $newpath);
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
        return $this->delete($dirname);
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     * @throws Exception
     */
    public function createDir($dirname, Config $config): bool|array
    {
        $path = $this->applyPathPrefix($dirname);

        try {
            $object = $this->client->createFolder($path);
        } catch (Exception $e) {
            throw new Exception('Unable to create new folder: ' . $dirname . ' ' . $e->getMessage());
        }

        return $object;
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     *
     * @return array file meta data
     * @throws Exception
     */
    public function getSize($path): array
    {
        return $this->getMetadata($path);
    }

    /**
     * @param string $path
     * @return array|bool
     * @throws Exception
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @param string $path
     * @return array
     */
    #[ArrayShape(['mimetype' => "string"])] public function getMimetype($path): array
    {
        return ['mimetype' => MimeType::detectByFilename($path)];
    }

    /**
     * @param string $path
     * @return bool|array
     * @throws Exception
     */
    public function getMetadata($path): array
    {
        $path = $this->applyPathPrefix($path);

        try {
            $object = $this->client->getMetadata($path);
        } catch (Exception $e) {
            throw new Exception('getMetadata from: ' . $path . ' ' . $e->getMessage());
        }

        return $object;
    }

    /**
     * @param string $path
     * @return array|false|void
     */
    public function getVisibility($path)
    {
        throw new Exception('Unable to set visibility : ' . $path . ' ' . get_class($this) . ' Content API does not support visibility.');
    }

    /**
     * @param string $path
     * @return array|bool
     * @throws Exception
     */
    public function has($path): array|bool
    {
        return $this->getMetadata($path);
    }

}
