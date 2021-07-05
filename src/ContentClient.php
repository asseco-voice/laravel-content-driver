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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Support\Str;

class ContentClient
{
    private string $token;

    private string $baseURL;

    private string $baseRestAPIUrl = '/v1/content/';

    private string $defaultRepository = 'dms';

    /**
     * ContentClient constructor.
     * @param string $token
     * @param string|null $baseURL
     * @param string|null $baseRestAPIUrl
     * @param string|null $defaultRepository
     */
    public function __construct(string $token, string $baseURL = null, string $baseRestAPIUrl = null, string $defaultRepository = null)
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
    private function setClient(string $method = 'GET', string $url = '', array $payload = []): Response
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
     * @return RepositoryList
     * @throws Exception
     */
    public function getRepositories(): RepositoryList
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . 'repositories';

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
        $recursive = $recursive ? 'true' : 'false';
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/' . $folder . '?kind=any' . '&subfolders=' . $recursive . '&page-size=' . $per_page . '&page=' . $page . '&sort-order=' . $order;

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
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/';
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
    public function folderExist(string $path, bool $recursive = false): bool
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
     * @param string $path
     * @return Document
     * @throws Exception
     */
    public function getDocumentMetadata(string $path): Document
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';

        return new Document($this->setClient('GET', $url)->json());
    }

    /**
     * @param string $path
     * @return Directory
     * @throws Exception
     */
    public function getDirectoryMetadata(string $path): Directory
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';

        return new Directory($this->setClient('GET', $url)->json());
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
        $folderId = $this->getDirectoryMetadata($folder);
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
        $filenameId = $this->getDocumentMetadata($path);
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
     * @param string $sourceFile
     * @param string $destinationFolder
     * @param string|null $destinationRepo
     * @param bool $overwriteIfExists
     * @return Response
     * @throws Exception
     */
    public function moveFile(string $sourceFile, string $destinationFolder, string $destinationRepo = null, bool $overwriteIfExists = true): Response
    {
        $sourceFile = $this->getDocumentMetadata($sourceFile);
        $destinationFolder = $this->getDirectoryMetadata($destinationFolder);
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

        return $response->getBody()->detach();
    }


    /**
     * @param string $path
     * @param $content
     * @param string|null $purpose
     * @param string|null $caseNumber
     * @param bool $overwriteIfExists
     * @return array
     * @throws Exception
     */
    public function uploadFile(string $path, $content, string $purpose = null, string $caseNumber = null, bool $overwriteIfExists = true): array
    {
        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $content);
        $tmpFile = new File($tmpFilePath);

        $filename = basename($path);
        $mediaType = $tmpFile->getMimeType($tmpFile);
        $path = (dirname($path) === '.') ?  '/' :  dirname($path) . '/';

        $content = new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );
        return $this->upload($path, $filename, $content, $mediaType, $overwriteIfExists);
    }

    /**
     * @param string $path
     * @param $filename
     * @param $content
     * @param $mediaType
     * @param bool $override
     *
     * @return Document
     * @throws Exception
     */
    public function upload(string $path, $filename, $content, $mediaType, bool $override): Document
    {
        $this->folderExist($path, true);
        $folder = $this->getDirectoryMetadata(dirname($path));
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'        => $content,
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
                ->attach('content-stream', $content, $filename)
                ->post($url, $payload);
            return new Document($res->json());
        } catch (Exception | RequestException $e) {
            Log::error("Couldn't get response: " . print_r($e->getMessage(), true));
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param  string  $path
     * @param $resource
     * @param $message
     * @param $caseNumber
     * @param  bool  $override
     *
     * @return Document
     * @throws Exception
     */
    public function uploadStream(string $path, $resource, $message = null, $caseNumber = null, bool $override = false): Document
    {
        #if (! $resource instanceof Illuminate\Http\UploadedFile ) {
        #    throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        #}

        $content = $resource->getContent();
        $filename = $resource->getClientOriginalName();
        $mediaType = $resource->getClientMimeType();

        return $this->upload($path, $filename, $content, $mediaType, $override);
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
            $response = $this->listDirectory($directory, $recursive, 10, ++$page);
            yield $response;
        } while ($this->responseHasNextPage(new ContentItemList($response)));
    }

    /**
     * @param Response $response
     * @param bool $json
     *
     * @return Response
     */
    public function responseContents(Response $response, bool $json = true): Response
    {
        $contents = $response->getBody()->getContents();

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
}
