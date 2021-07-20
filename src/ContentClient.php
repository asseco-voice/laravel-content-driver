<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Responses\ContentItemList;
use Asseco\ContentFileStorageDriver\Responses\Directory;
use Asseco\ContentFileStorageDriver\Responses\Document;
use Asseco\ContentFileStorageDriver\Responses\RepositoryList;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class ContentClient
{
    private string $token;

    private string $baseURL;

    private string $pathPrefix;

    private string $defaultRepository;

    /**
     * ContentClient constructor.
     * @param string $token
     * @param string $pathPrefix
     * @param string $baseURL
     * @param string $defaultRepository
     */
    public function __construct(string $token, string $pathPrefix = '', string $baseURL = '', string $defaultRepository = '')
    {
        $this->token = $token;
        $this->baseURL = $baseURL;
        $this->pathPrefix = $pathPrefix;
        $this->defaultRepository = $defaultRepository;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $payload
     * @return Response
     * @throws Exception
     */
    private function setClient(string $method = 'GET', string $url = '', array $payload = []): Response
    {
        try {
            return Http::withToken($this->token)
                ->withHeaders([
                    'Allow' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method(
                    $url,
                    $payload
                );
        } catch (Exception | RequestException $e) {
            Log::error("Couldn't get response: " . print_r($e->getMessage(), true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $pathPrefix
     */
    public function setPrefixPath($pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;
    }

    /**
     * @return string
     */
    public function getPrefixPath(): string
    {
        return $this->pathPrefix;
    }

    /**
     * @return RepositoryList
     * @throws Exception
     */
    public function getRepositories(): RepositoryList
    {
        $url = $this->baseURL . 'repositories';

        return new RepositoryList($this->setClient('GET', $url)->json());
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
    public function listDirectory(string $folder = '', bool $recursive = false, int $per_page = 10, int $page = 0, string $order = 'asc'): Response
    {
        $folder = $this->normalizePath($folder);
        $recursive = $recursive ? 'true' : 'false';
        $url = $this->baseURL . $this->defaultRepository . '/' . $folder . '?kind=any' . '&subfolders=' . $recursive . '&page-size=' . $per_page . '&page=' . $page . '&sort-order=' . $order;

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $name
     * @param string $path
     * @return Directory
     * @throws Exception
     */
    public function createFolder(string $name, string $path = '/'): Directory
    {
        $url = $this->baseURL . $this->defaultRepository . '/folders/';
        $payload = [
            'name'              => $name,
            'path'              => $path,
            'kind'              => 'folder',
            'folder-purpose'    => 'generic-folder',
        ];

        return new Directory($this->setClient('POST', $url, $payload)->json());
    }

    /**
     * @param string $path
     * @param false $recursive
     * @return bool
     * @throws Exception
     */
    public function folderExist(string $path, bool $recursive = true): bool
    {
        if ($recursive) {
            $fullPath = '/';
            $fullPathOld = '/';
            $folders = explode('/', $path);
            foreach ($folders as $folder) {
                $fullPath .= $folder;
                if (!$this->folderExist($fullPath)) {
                    $this->createFolder($folder, $fullPathOld);
                }
                $fullPathOld .= $folder;
            }
        }
        $url = $this->baseURL . $this->defaultRepository . $path . '/metadata';
        $response = $this->setClient('GET', $url);

        return in_array($response->status(), [200, 440]);
    }

    /**
     * @param string $path
     * @return Document
     * @throws Exception
     */
    public function getDocumentMetadata(string $path): Document
    {
        $path = $this->normalizePath($path);
        $url = $this->baseURL . $this->defaultRepository . $path . '/metadata';

        return new Document($this->setClient('GET', $url)->json());
    }

    /**
     * @param string $path
     * @return Directory
     * @throws Exception
     */
    public function getDirectoryMetadata(string $path): Directory
    {
        $path = $this->normalizePath($path);
        $url = $this->baseURL . $this->defaultRepository . $path . '/metadata';

        return new Directory($this->setClient('GET', $url)->json());
    }

    /**
     * @param string $filename
     * @return Response
     * @throws Exception
     */
    public function getFile(string $filename = '/'): Response
    {
        $filenameId = $this->getDocumentMetadata($filename);
        $url = $this->baseURL . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $filename
     * @return false|resource
     * @throws Exception
     */
    public function getStreamFile(string $filename = '/')
    {
        $filenameId = $this->getDocumentMetadata($filename);
        $url = $this->baseURL . $this->defaultRepository . '/documents/' . $filenameId->id;
        $content = $this->setClient('GET', $url)->body();

        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $content);

        return fopen($tmpFilePath, 'rb');
    }

    /**
     * @param string $folder
     * @param bool $deleteContentWithSubFolders
     * @return Response
     * @throws Exception
     */
    public function deleteFolders(string $folder, bool $deleteContentWithSubFolders = true): Response
    {
        $folder = $this->normalizePath($folder);
        $deleteContentWithSubFolders = $deleteContentWithSubFolders ? 'true' : 'false';
        $folderId = $this->getDirectoryMetadata($folder);
        $url = $this->baseURL . $this->defaultRepository . '/folders/' . $folderId->id . '?delete-content-and-subfolders=' . $deleteContentWithSubFolders;
        $request = $this->setClient('DELETE', $url);

        return in_array($request->status(), [200]);
    }

    /**
     * @param string $path
     * @return Response
     * @throws Exception
     */
    public function deleteFile(string $path): Response
    {
        $path = $this->normalizePath($path);
        $filenameId = $this->getDocumentMetadata($path);
        $url = $this->baseURL . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->setClient('DELETE', $url);
    }

    /**
     * @param string $search
     * @return Response
     * @throws Exception
     */
    public function searchFolders(string $search): Response
    {
        $url = $this->baseURL . $this->defaultRepository . '/search?q=' . $search;

        return $this->setClient('GET', $url);
    }

    /**
     * @param string $sourceFile
     * @param string $destinationFolder
     * @param string|null $destinationRepo
     * @param bool $overwriteIfExists
     * @return bool
     * @throws Exception
     */
    public function moveFile(string $sourceFile, string $destinationFolder, string $destinationRepo = null, bool $overwriteIfExists = true): bool
    {
        $sourceFile = $this->getDocumentMetadata($this->normalizePath($sourceFile));
        $destinationFolder = $this->getDirectoryMetadata($this->normalizePath($destinationFolder));
        $url = $this->baseURL . $this->defaultRepository . '/documents/' . $sourceFile->id . '/move';
        $payload = [
            'destination-folder-id'     => $destinationFolder->id,
            'destination-repo'          => $destinationRepo ?? $this->defaultRepository,
            'overwrite'                 => $overwriteIfExists,
        ];
        $request = $this->setClient('POST', $url, $payload);

        return in_array($request->status(), [200, 204]);
    }

    /**
     * @param $path
     *
     * @return false|resource
     * @throws Exception
     */
    public function readStream($path)
    {
        $path = $this->normalizePath($path);

        return $this->getStreamFile($path);
    }

    /**
     * @param $path
     *
     * @return string
     * @throws Exception
     */
    public function readRaw($path): string
    {
        $path = $this->normalizePath($path);
        $response = $this->getFile($path);

        return $response->body();
    }

    /**
     * @param string $path
     * @param $contents
     * @param string|null $purpose
     * @param string|null $caseNumber
     * @param bool $overwriteIfExists
     * @return Document
     * @throws Exception
     */
    public function uploadFile(string $path, $contents, string $purpose = null, string $caseNumber = null, bool $overwriteIfExists = false): Document
    {
        $mimeTypeDetector = new ExtensionMimeTypeDetector();
        $mediaType = $mimeTypeDetector->detectMimeTypeFromPath($path);
        $purpose = $purpose ?? trim($this->pathPrefix, '/');
        $caseNumber = $caseNumber ?? 'record id';

        return $this->upload($path, basename($path), $contents, $mediaType, $purpose, $caseNumber, $overwriteIfExists);
    }

    /**
     * @param string $path
     * @param $filename
     * @param $contents
     * @param $mediaType
     * @param null $purpose
     * @param null $caseNumber
     * @param bool $override
     *
     * @return Document
     * @throws Exception
     */
    public function upload(string $path, $filename, $contents, $mediaType, $purpose = null, $caseNumber = null, bool $override = false): Document
    {
        $path = $this->normalizePath($path);
        $this->folderExist($path);
        $folder = $this->getDirectoryMetadata(dirname($path));
        $url = $this->baseURL . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'        => $contents,
            'name'                  => $filename,
            'media-type'            => $mediaType,
            'filing-purpose'        => $purpose ?? 'service',
            'filing-case-number'    => $caseNumber ?? 'record id',
            'overwrite-if-exists'   => $override ? 'true' : 'false',
        ];

        try {
            $res = Http::withToken($this->token)
                ->withHeaders([
                    'Allow' => 'application/json',
                ])
                ->attach('content-stream', $contents, $filename)
                ->post($url, $payload);

            return new Document($res->json());
        } catch (Exception $e) {
            Log::error("Couldn't get response: " . print_r($e->getMessage(), true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param  string  $path
     * @param $resource
     * @param  string  $message
     * @param  bool  $override
     *
     * @return Document
     * @throws Exception
     */
    public function uploadStream(string $path, $resource, string $message, bool $override = false): Document
    {
        $path = $this->normalizePath($path);
        if (!is_resource($resource)) {
            throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        }

        return $this->upload($path, stream_get_contents($resource), $message, $override);
    }

    /**
     * @param string $path
     * @return bool
     * @throws Exception
     */
    public function delete(string $path): bool
    {
        $path = $this->normalizePath($path);
        $response = $this->deleteFile($path);

        return in_array($response->status(), [200]);
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
        $directory = $this->normalizePath($directory);
        $page = 1;
        do {
            $response = $this->listDirectory($directory, $recursive, 10, ++$page);
            yield $response;
        } while ($this->responseHasNextPage(new ContentItemList($response)));
    }

    /**
     * @param Response $response
     * @param bool $json
     *
     * @return string
     */
    public function responseContents(Response $response, bool $json = true): string
    {
        $contents = $response->body();

        return ($json) ? json_decode($contents, true) : $contents;
    }

    /**
     * @param ContentItemList $response
     *
     * @return bool
     */
    public function responseHasNextPage(ContentItemList $response): bool
    {
        if ($response->totalPages > 0 && $response->page != $response->totalPages) {
            return true;
        }

        return false;
    }

    public function normalizePath($path)
    {
        if (strpos($path, $this->pathPrefix) === false) {
            $path = $this->pathPrefix . $path;
        }

        return $path;
    }
}
