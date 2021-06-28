<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\Chassis\App\Facades\Iam;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentClient
{
    private $token = '';

    private $baseURL = '';

    private $baseRestAPIUrl = '/v1/content/';

    private $defaultRepository = 'dms';

    public function __construct($baseURL = null, $baseRestAPIUrl = null, $defaultRepository = null)
    {
        $this->baseURL = $baseURL ?? $this->baseURL;
        $this->baseRestAPIUrl = $baseRestAPIUrl ?? $this->baseRestAPIUrl;
        $this->defaultRepository = $defaultRepository ?? $this->defaultRepository;
        $this->token = Iam::getServiceToken();
    }

    /**
     * @throws Exception
     */
    private function setClient($method = 'GET', $url, $payload = [])
    {
        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Allow' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method(
                    $url,
                    $payload
                );
            Log::info('response: ' . print_r($response, true));

            return $response;
        } catch (Exception | RequestException $e) {
            Log::error("Couldn't get response: " . print_r($e->getMessage(), true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getRepositories()
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . 'repositories';

        return $this->setClient('GET', $url)->object();
    }

    public function setDefaultRepositories($defaultRepository)
    {
        return $this->defaultRepository = $defaultRepository;
    }

    /**
     * @throws Exception
     */
    public function listFolder($folder = '', $recursive = false, $per_page = 100, $page = 0)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/' . $folder;

        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function createFolder($name, $path = '/')
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/';
        $payload = [
            'name'              => $name,
            'path'              => $path,
            'kind'              => 'folder',
            'folder-purpose'    => 'generic-folder',
        ];

        return $this->setClient('POST', $url, $payload)->object();
    }

    /**
     * @throws Exception
     */
    public function folderExist($path, $recursive = false): bool
    {
        if ($recursive) {
            $fullPath = '';
            $folders = array_reverse(explode('/', $path));
            foreach ($folders as $folder) {
                $fullPath .= '/' . $folder;
                if (!$this->folderExist($fullPath, false)) {
                    $this->createFolder($folder, dirname($fullPath));
                }
            }
        }
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';
        $response = $this->setClient('GET', $url);

        return in_array($response->status(), [200, 440]);
    }

    /**
     * @throws Exception
     */
    public function getMetadata($path)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';

        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function getFile($filename = '/')
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $filename;

        return $this->setClient('GET', $url);
    }

    /**
     * @throws Exception
     */
    public function deleteFolders($folder, $deleteContentWithSubFolders = true)
    {
        $deleteContentWithSubFolders = $deleteContentWithSubFolders ? 'true' : 'false';
        $folderId = $this->getMetadata($folder);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folderId->id . '?delete-content-and-subfolders=' . $deleteContentWithSubFolders;

        return $this->setClient('DELETE', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function deleteFile($path)
    {
        $filenameId = $this->getMetadata($path);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->setClient('DELETE', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function searchFolders($search)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/search?q=' . $search;

        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function uploadFile($path, $content, $purpose = null, $caseNumber = null, $overwriteIfExists = true)
    {
        $this->folderExist($path, true);
        $folder = $this->getMetadata(dirname($path));
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'        => $content,
            'name'                  => $content->getClientOriginalName(),
            'media-type'            => $content->getClientMimeType(),
            'filing-purpose'        => $purpose ?? 'service',
            'filing-case-number'    => $caseNumber ?? 'record id',
            'overwrite-if-exists'   => $overwriteIfExists ? 'true' : 'false',
        ];

        $r = Http::withToken($this->token)
            ->withHeaders([
                'Allow' => 'application/json',
            ])
            ->attach('content-stream', $content->getContent(), $content->getClientOriginalName())
            ->post($url, $payload);

        //dd($folder->id);
        return $r;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function moveFile($sourceFile, $destinationFolder, $destinationRepo = null, $overwriteIfExists = true)
    {
        $sourceFile = $this->getMetadata($sourceFile);
        $folder = $this->getMetadata($destinationFolder);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $sourceFile->id . '/move';
        $payload = [
            'destination-folder-id'     => $folder->id,
            'destination-repo'          => $destinationRepo ?? $this->defaultRepository,
            'overwrite'                 => $overwriteIfExists,
        ];

        return $this->setClient('POST', $url, $payload)->object();
    }

    /**
     * @param $path
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function read($path)
    {
        //$path = urlencode($path);
        $metadata = $this->getMetadata($path);

        return $metadata;
    }

    /**
     * @param $path
     *
     * @return resource|null
     * @throws Exception
     */
    public function readStream($path)
    {
        $path = urlencode($path);

        $response = $this->getFile($path);

        return $response->getBody()->detach();
    }

    /**
     * @param $path
     *
     * @return resource|null
     * @throws Exception
     */
    public function readRaw($path)
    {
        //$path = urlencode($path);

        $response = $this->getFile($path);

        return $response->getBody()->detach();
    }

    /**
     * @param  string  $path
     * @param    $contents
     * @param  string  $message
     * @param  bool  $override
     *
     * @return array
     * @throws Exception
     */
    public function upload(string $path, $contents, string $message, string $caseId = null, bool $override = false)
    {
        //$path = urlencode($path);
        //return $this->responseContents(
        return $this->uploadFile($path, $contents, $message, $caseId, $override);
        //);
    }

    /**
     * @param  string  $path
     * @param $resource
     * @param  string  $message
     * @param  bool  $override
     *
     * @return array
     * @throws Exception
     */
    public function uploadStream(string $path, $resource, string $message, bool $override = false): array
    {
        if (!is_resource($resource)) {
            throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        }

        return $this->upload($path, stream_get_contents($resource), $message, $override);
    }

    /**
     * @param  string  $path
     * @param  string  $commitMessage
     *
     * @throws Exception
     */
    public function delete(string $path)
    {
        //$path = urlencode($path);

        return $this->deleteFile($path);
    }

    /**
     * @param  string|null  $directory
     * @param  bool  $recursive
     *
     * @return iterable
     * @throws Exception
     */
    public function tree(string $directory = null, bool $recursive = false): iterable
    {
        if ($directory === '/' || $directory === '') {
            $directory = null;
        }

        $page = 1;

        do {
            $response = $this->listFolder($directory, $recursive, 100, $page++);
            yield $this->responseContents($response);
        } while ($this->responseHasNextPage($response));
    }

    /**
     * @param Response $response
     * @param bool $json
     *
     * @return mixed
     */
    private function responseContents(Response $response, $json = true): mixed
    {
        $contents = $response->getBody()
            ->getContents();

        return ($json) ? json_decode($contents, true) : $contents;
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    private function responseHasNextPage(Response $response): bool
    {
        if ($response->hasHeader('X-Next-Page')) {
            return !empty($response->getHeader('X-Next-Page')[0] ?? '');
        }

        return false;
    }
}
