<?php
namespace Asseco\ContentFileStorageDriver;

use Asseco\Chassis\App\Facades\Iam;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ContentClient {

    private $token = null;

    private $baseURL = 'http://10.135.11.98:7075'; // for my testing

    private $baseRestAPIUrl = '/v1/content/';

    private $defaultRepository = 'dms';

    public function __construct($baseURL = null, $baseRestAPIUrl = null, $defaultRepository = null)
    {
        $this->baseURL = $baseURL ?? $this->baseURL;
        $this->baseRestAPIUrl = $baseRestAPIUrl ?? $this->baseRestAPIUrl ;
        $this->defaultRepository = $defaultRepository ?? $this->defaultRepository;

        #if (auth()->user()) {
           # $this->token = auth()->user()->getTokenAsString();
        #} else if (false /* todo: */) {
         #   $this->token = Iam::getServiceToken();
        #} else {
            $this->token = Http::asForm()
                ->withHeaders([
                    'Allow' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->POST(
                    'http://10.135.11.98:7072/auth/realms/evil/protocol/openid-connect/token',
                    [
                        'client_id'             => 'livepoc_web',
                        'grant_type'            => 'password',
                        'username'              => 'live',
                        'password'              => 'live'
                    ]
                )->object()->access_token;
        #}
    }

    /**
     * @throws Exception
     */
    private function setClient($method = 'GET', $url, $payload = [])
    {
        try {
            $response = Http::asJson()
                #->withToken(Iam::getServiceToken()) # TODO: or user token???
                ->$method(
                    $url,
                    $payload
                )->throw();
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
    public function listFolder($folder = '', $recursive = false, $per_page  = 100, $page = 0)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/' . $folder;
        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function createFolder($name, $path = '/')
    {
        if (! $this->folderExists($path, true)) {
            $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'folders';
            $payload = [
                'kind'              => 'folder',
                'name'              => $name,
                'path'              => $path,
                'folder-purpose'    => 'generic-folder'
            ];
            return $this->setClient('POST', $url, $payload)->object();
        }
    }

    /**
     * @throws Exception
     */
    public function folderExist($path, $recursive = false): bool
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . $path . '/metadata';
        if ($recursive) {
            $fullPath = '/';
            $folders = array_reverse(explode('/', $path));
            foreach($folders as $folder) {
                if (! $this->folderExist($folder, false)) {
                    $this->createFolder($folder, $fullPath);
                    $fullPath .= $folder;
                }
            }
        }
        $response = $this->setClient('GET', $url);
        return in_array($response->status() , [200, 440]) ;
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
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'documents' . $filename;
        return $this->setClient('GET', $url);
    }

    /**
     * @throws Exception
     */
    public function deleteFolders($folder, $deleteContentWithSubFolders=true)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'folders' . $folder .'?delete-content-and-subfolders=' . $deleteContentWithSubFolders;
        return $this->setClient('DELETE', $url)->object();
    }


    /**
     * @throws Exception
     */
    public function deleteFile($path)
    {
        $filenameId = $this->getMetadata($path, false);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'documents' . $filenameId->id;
        return $this->setClient('DELETE', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function searchFolders($search)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'search?q=' . $search;
        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function uploadFile($content, $path, $purpose = null, $caseNumber = null, $overwriteIfExists = true)
    {
        $folder = $this->getMetadata($path, false);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . '/folders/' . $folder->id;

        $payload = [
            'content-stream'        => $content,
            'name'                  => $content->getClientOriginalName(),
            'media-type'            => $content->getClientMimeType(),
            'filing-purpose'        => $purpose ?? 'service',
            'filing-case-number'    => $caseNumber ?? 'record id',
            'overwrite-if-exists'   => $overwriteIfExists ? 'true' : 'false'
        ];

        $response = Http::withToken($this->token)
            ->withHeaders([
                'Allow' => 'application/json',
            ])
            ->attach('content-stream', $content->getContent(), $content->getClientOriginalName())
            ->post($url, $payload);
        return $response->json();
    }

    /**
     *
     * @return array
     * @throws Exception
     */
    public function moveFile($sourceFile, $destinationFolder, $destinationRepo, $overwriteIfExists = true)
    {
        $sourceFile = $this->getMetadata($sourceFile, false);
        $folder = $this->getMetadata($destinationFolder, false);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'documents/' . $sourceFile->id . '/move';
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
        #$path = urlencode($path);
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
        $path = urlencode($path);

        $response = $this->getFile($path);

        return $response->getBody()->detach();
    }

    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  string  $message
     * @param  bool  $override
     *
     * @return array
     * @throws Exception
     */
    public function upload(string $path, string $contents, string $message, bool $override = false): array
    {
        #$path = urlencode($path);
        return $this->responseContents($this->uploadFile($contents, $path, $message, $message, $override));
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
            throw new InvalidArgumentException(sprintf('Argument must be a valid resource type. %s given.',
                gettype($resource)));
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
        #$path = urlencode($path);

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
            return !empty($response->getHeader('X-Next-Page')[0] ?? "");
        }

        return false;
    }
}
