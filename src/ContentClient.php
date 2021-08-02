<?php

namespace Asseco\ContentFileStorageDriver;

use Asseco\ContentFileStorageDriver\Responses\ContentItemList;
use Asseco\ContentFileStorageDriver\Responses\Directory;
use Asseco\ContentFileStorageDriver\Responses\Document;
use Asseco\ContentFileStorageDriver\Responses\RepositoryList;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class ContentClient
{
    private string $token;
    private string $baseUrl;
    private string $pathPrefix;
    private string $defaultRepository;
    private PendingRequest $client;

    /**
     * ContentClient constructor.
     * @param string $token
     * @param string $pathPrefix
     * @param string $baseUrl
     * @param string $defaultRepository
     */
    public function __construct(string $token, string $pathPrefix = '', string $baseUrl = '', string $defaultRepository = '')
    {
        $this->token = $token;
        $this->baseUrl = $baseUrl;
        $this->pathPrefix = $pathPrefix;
        $this->defaultRepository = $defaultRepository;

        $this->client = Http::withToken($this->token)
            ->withHeaders([
                'Allow'        => 'application/json',
                'Content-Type' => 'application/json',
            ]);
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
        $url = $this->baseUrl . 'repositories';

        return new RepositoryList(
            $this->client->get($url)->throw()->json()
        );
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
        $url = $this->baseUrl . $this->defaultRepository . '/' . $folder . '?kind=any' . '&subfolders=' . $recursive . '&page-size=' . $per_page . '&page=' . $page . '&sort-order=' . $order;

        return $this->client->get($url)->throw();
    }

    /**
     * @param string $name
     * @param string $path
     * @return Directory
     * @throws Exception
     */
    public function createFolder(string $name, string $path = '/'): Directory
    {
        $url = $this->baseUrl . $this->defaultRepository . '/folders/';
        $payload = [
            'name'           => $name,
            'path'           => $path,
            'kind'           => 'folder',
            'folder-purpose' => 'generic-folder',
        ];

        return new Directory(
            $this->client->post($url, $payload)->throw()->json()
        );
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

        $url = $this->baseUrl . $this->defaultRepository . $path . '/metadata';
        $response = $this->client->get($url)->throw();

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
        $url = $this->baseUrl . $this->defaultRepository . $path . '/metadata';

        return new Document(
            $this->client->get($url)->throw()->json()
        );
    }

    /**
     * @param string $path
     * @return Directory
     * @throws Exception
     */
    public function getDirectoryMetadata(string $path): Directory
    {
        $path = $this->normalizePath($path);
        $url = $this->baseUrl . $this->defaultRepository . $path . '/metadata';

        return new Directory(
            $this->client->get($url)->throw()->json()
        );
    }

    /**
     * @param string $filename
     * @return Response
     * @throws Exception
     */
    public function getFile(string $filename = '/'): Response
    {
        $filenameId = $this->getDocumentMetadata($filename);
        $url = $this->baseUrl . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->client->get($url)->throw();
    }

    /**
     * @param string $filename
     * @return false|resource
     * @throws Exception
     */
    public function getStreamFile(string $filename = '/')
    {
        $filenameId = $this->getDocumentMetadata($filename);
        $url = $this->baseUrl . $this->defaultRepository . '/documents/' . $filenameId->id;
        $content = $this->client->get($url)->throw()->body();

        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $content);

        return fopen($tmpFilePath, 'rb');
    }

    /**
     * @param string $folder
     * @param bool $deleteContentWithSubFolders
     * @return bool
     * @throws Exception
     */
    public function deleteFolders(string $folder, bool $deleteContentWithSubFolders = true): bool
    {
        $folder = $this->normalizePath($folder);
        $deleteContentWithSubFolders = $deleteContentWithSubFolders ? 'true' : 'false';
        $folderId = $this->getDirectoryMetadata($folder);
        $url = $this->baseUrl . $this->defaultRepository . '/folders/' . $folderId->id . '?delete-content-and-subfolders=' . $deleteContentWithSubFolders;
        $request = $this->client->delete($url)->throw();

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
        $url = $this->baseUrl . $this->defaultRepository . '/documents/' . $filenameId->id;

        return $this->client->delete($url)->throw();
    }

    /**
     * @param string $search
     * @return Response
     * @throws Exception
     */
    public function searchFolders(string $search): Response
    {
        $url = $this->baseUrl . $this->defaultRepository . '/search?q=' . $search;

        return $this->client->get($url)->throw();
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
        $url = $this->baseUrl . $this->defaultRepository . '/documents/' . $sourceFile->id . '/move';
        $payload = [
            'destination-folder-id' => $destinationFolder->id,
            'destination-repo'      => $destinationRepo ?? $this->defaultRepository,
            'overwrite'             => $overwriteIfExists,
        ];
        $request = $this->client->post($url, $payload)->throw();

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
        $purpose = $purpose ?? trim($this->pathPrefix, '/');
        $caseNumber = $caseNumber ?? 'record id';

        return $this->upload($path, basename($path), $contents, $purpose, $caseNumber, $overwriteIfExists);
    }

    /**
     * @param string $path
     * @param $filename
     * @param $contents
     * @param null $purpose
     * @param null $caseNumber
     * @param bool $override
     *
     * @return Document
     * @throws Exception
     */
    public function upload(string $path, $filename, $contents, $purpose = null, $caseNumber = null, bool $override = false): Document
    {
        $mimeTypeDetector = new ExtensionMimeTypeDetector();
        $mediaType = $mimeTypeDetector->detectMimeTypeFromPath($path);

        $path = $this->normalizePath($path);
        $this->folderExist($path);
        $folder = $this->getDirectoryMetadata(dirname($path));
        $url = $this->baseUrl . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'      => $contents,
            'name'                => $filename,
            'media-type'          => $mediaType,
            'filing-purpose'      => $purpose ?? 'service',
            'filing-case-number'  => $caseNumber ?? 'record id',
            'overwrite-if-exists' => $override ? 'true' : 'false',
        ];

        try {
            $res = Http::withToken($this->token)
                ->withHeaders([
                    'Allow' => 'application/json',
                ])
                ->attach('content-stream', $contents, $filename)
                ->post($url, $payload)
                ->throw();

            return new Document($res->json());
        } catch (Exception $e) {
            Log::error("Couldn't get response for path '{$path}': " . print_r($e->getMessage(), true));
            Log::error("Filename: " . print_r($filename, true));
            Log::error("Contents: " . print_r($contents, true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param $resource
     * @param null $purpose
     * @param null $caseNumber
     * @param bool $override
     *
     * @return Document
     * @throws Exception
     */
    public function uploadStream(string $path, $resource, $purpose = null, $caseNumber = null, bool $override = false): Document
    {
        $path = $this->normalizePath($path);

        if (!is_resource($resource)) {
            throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        }

        return $this->upload($path, basename($path), stream_get_contents($resource), $purpose, $caseNumber, $override);
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
     * @param string $directory
     * @param bool $recursive
     *
     * @return iterable
     * @throws Exception
     */
    public function tree(string $directory = '/', bool $recursive = false): iterable
    {
        $directory = $this->normalizePath($directory);
        $page = 1;

        do {
            yield $this->listDirectory($directory, $recursive, 10, ++$page);

        } while ($this->responseHasNextPage(
            new ContentItemList(
                $this->listDirectory($directory, $recursive, 10, ++$page)
            )
        ));
    }

    /**
     * @param ContentItemList $response
     *
     * @return bool
     */
    protected function responseHasNextPage(ContentItemList $response): bool
    {
        return $response->totalPages > 0 && $response->page != $response->totalPages;
    }

    protected function normalizePath($path)
    {
        if (strpos($path, $this->pathPrefix) === false) {
            $path = $this->pathPrefix . $path;
        }

        return $path;
    }
}
