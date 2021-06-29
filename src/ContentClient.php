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
    private string $token;

    private string $baseURL;

    private string $baseRestAPIUrl = '/v1/content/';

    private string $defaultRepository = 'dms';

    /**
     * ContentClient constructor.
     * @param string $token
     * @param string $baseURL
     * @param string|null $baseRestAPIUrl
     * @param string|null $defaultRepository
     */
    public function __construct(string $token, string $baseURL, string $baseRestAPIUrl = null, string $defaultRepository = null)
    {
        $this->token = $token;
        $this->baseURL = $baseURL ?? $this->baseURL;
        $this->baseRestAPIUrl = $baseRestAPIUrl ?? $this->baseRestAPIUrl;
        $this->defaultRepository = $defaultRepository ?? $this->defaultRepository;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $payload
     * @return Response
     * @throws Exception
     */
    private function setClient(string $method = 'GET', string $url, array $payload = []): Response
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

            Log::debug('response: ' . print_r($response, true));

            return $response;
        } catch (Exception | RequestException $e) {
            Log::error("Couldn't get response: " . print_r($e->getMessage(), true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function getRepositories(): Response
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . 'repositories';

        return $this->setClient('GET', $url);
    }

    /**
     * @param $defaultRepository
     * @return string
     */
    public function setDefaultRepositories($defaultRepository): string
    {
        return $this->defaultRepository = $defaultRepository;
    }

    /**
     * @param string $folder
     * @param false $recursive
     * @param int $per_page
     * @param int $page
     * @param string $order
     * @return Response
     * @throws Exception
     */
    public function listFolder(string $folder = '', bool $recursive = false, int $per_page = 10, int $page = 0, string $order = 'asc') : Response
    {
        $recursive = $recursive ? 'true' : 'false';
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/' . $folder . '?kind=any' .  '&subfolders=' . $recursive . '&page-size=' . $per_page . '&page=' . $page . '&sort-order=' . $order;
        return $this->setClient('GET', $url);
    }

    /**
     * @param string $name
     * @param string $path
     * @return Response
     * @throws Exception
     */
    public function createFolder(string $name, string $path = '/'): Response
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/';
        $payload = [
            'name'              => $name,
            'path'              => $path,
            'kind'              => 'folder',
            'folder-purpose'    => 'generic-folder',
        ];

        return $this->setClient('POST', $url, $payload);
    }

    /**
     * @param string $path
     * @param false $recursive
     * @return bool
     * @throws Exception
     */
    public function folderExist(string $path, bool $recursive = false): bool
    {
        if ($recursive) {
            $fullPath = '';
            $folders = array_reverse(explode('/', $path));
            foreach ($folders as $folder) {
                $fullPath .= '/' . $folder;
                if (! $this->folderExist($fullPath, false)) {
                    $this->createFolder($folder, dirname($fullPath));
                }
            }
        }
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';
        $response = $this->setClient('GET', $url);

        return in_array($response->status(), [200, 440]);
    }

    /**
     * @param string $path
     * @return Response
     * @throws Exception
     */
    public function getMetadata(string $path): Response
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $filename
     * @return Response
     * @throws Exception
     */
    public function getFile(string $filename = '/'): Response
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $filename;

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $folder
     * @param bool $deleteContentWithSubFolders
     * @return Response
     * @throws Exception
     */
    public function deleteFolders(string $folder, bool $deleteContentWithSubFolders = true): Response
    {
        $deleteContentWithSubFolders = $deleteContentWithSubFolders ? 'true' : 'false';
        $folderId = $this->getMetadata($folder)->object();
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folderId->id . '?delete-content-and-subfolders=' . $deleteContentWithSubFolders;

        return $this->setClient('DELETE', $url);
    }

    /**
     * @param string $path
     * @return Response
     * @throws Exception
     */
    public function deleteFile(string $path): Response
    {
        $filenameId = $this->getMetadata($path)->object();
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->setClient('DELETE', $url);
    }

    /**
     * @param string $search
     * @return Response
     * @throws Exception
     */
    public function searchFolders(string $search): Response
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/search?q=' . $search;

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $path
     * @param $content
     * @param string|null $purpose
     * @param string|null $caseNumber
     * @param bool $overwriteIfExists
     * @return Response
     * @throws Exception
     */
    public function uploadFile(string $path, $content, string $purpose = null, string $caseNumber = null, bool $overwriteIfExists = true): Response
    {
        $this->folderExist($path, true);
        $folder = $this->getMetadata(dirname($path))->object();
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'        => $content,
            'name'                  => $content->getClientOriginalName(),
            'media-type'            => $content->getClientMimeType(),
            'filing-purpose'        => $purpose ?? 'service',
            'filing-case-number'    => $caseNumber ?? 'record id',
            'overwrite-if-exists'   => $overwriteIfExists ? 'true' : 'false',
        ];

        return Http::withToken($this->token)
            ->withHeaders([
                'Allow' => 'application/json',
            ])
            ->attach('content-stream', $content->getContent(), $content->getClientOriginalName())
            ->post($url, $payload);
    }

    /**
     * @param string $sourceFile
     * @param string $destinationFolder
     * @param string|null $destinationRepo
     * @param bool $overwriteIfExists
     * @return Response
     * @throws Exception
     */
    public function moveFile(string $sourceFile, string $destinationFolder, string $destinationRepo = null, bool $overwriteIfExists = true): Response
    {
        $sourceFile = $this->getMetadata($sourceFile)->object();
        $destinationFolder = $this->getMetadata($destinationFolder)->object();
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/documents/' . $sourceFile->id . '/move';
        $payload = [
            'destination-folder-id'     => $destinationFolder->id,
            'destination-repo'          => $destinationRepo ?? $this->defaultRepository,
            'overwrite'                 => $overwriteIfExists,
        ];

        return $this->setClient('POST', $url, $payload);
    }

    /**
     * @param $path
     *
     * @return resource|null
     * @throws Exception
     */
    public function readStream($path)
    {
        $response = $this->getFile($path);

        if (! $response) {
            return false;
        }

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
        $response = $this->getFile($path);

        if (! $response) {
            return false;
        }

        return $response->getBody()->detach();
    }

    /**
     * @param string $path
     * @param $contents
     * @param string $message
     * @param string|null $caseId
     * @param bool $override
     *
     * @return Response
     * @throws Exception
     */
    public function upload(string $path, $contents, string $message, string $caseId = null, bool $override = false): Response
    {
        return $this->uploadFile($path, $contents, $message, $caseId, $override);
    }

    /**
     * @param  string  $path
     * @param $resource
     * @param  string  $message
     * @param  bool  $override
     *
     * @return Response
     * @throws Exception
     */
    public function uploadStream(string $path, $resource, string $message, bool $override = false): Response
    {
        if (!is_resource($resource)) {
            throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        }

        return $this->upload($path, stream_get_contents($resource), $message, $override);
    }

    /**
     * @param string $path
     * @return Response
     * @throws Exception
     */
    public function delete(string $path): Response
    {
        return $this->deleteFile($path);
    }

    /**
     * @param  string  $directory
     * @param  bool  $recursive
     *
     * @return iterable
     * @throws Exception
     */
    public function tree(string $directory = '/', bool $recursive = false): iterable
    {
        $page = 1;
        do {
            $response = $this->listFolder($directory, $recursive, 10, ++$page);
            yield $response;
        } while ($this->responseHasNextPage($response));
    }

    /**
     * @param Response $response
     * @param bool $json
     *
     * @return Response
     */
    private function responseContents(Response $response, bool $json = true): Response
    {
        $contents = $response->getBody()->getContents();

        return ($json) ? json_decode($contents, true) : $contents;
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    private function responseHasNextPage(Response $response): bool
    {
        if ($response->{total-pages} > 0 && $response->{page} <> $response->{total-pages}) {
            return true;
        }

        return false;
    }
}
