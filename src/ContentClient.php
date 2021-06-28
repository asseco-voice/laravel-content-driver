<?php
namespace App;

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

    public function __construct()
    {
        if (! empty(config('content.baseURL'))) {
            $this->baseURL = config('content.baseURL');
        }
        if (auth()->user()) {
           # $this->token = auth()->user()->getTokenAsString();
        } else if (false /* todo: */) {
            $this->token = Iam::getServiceToken();
        } else {
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
        }
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
    public function listFolder($folder = '')
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
    public function folderMetadata($path)
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
        return $this->setClient('GET', $url)->object();
    }

    /**
     * @throws Exception
     */
    public function deleteFolders($folder, $deleteContentWithSubFolders=true)
    {
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'folders' . $folder .'?delete-content-and-subfolders=' . $deleteContentWithSubFolders;
        return $this->setClient('GET', $url)->object();
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
        $folder = $this->folderMetadata($path, false);
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
     * @throws Exception
     */
    public function moveFile($destinationFolder, $destinationRepo, $overwriteIfExists = true)
    {
        $folder = $this->folderMetadata($destinationFolder, false);
        $url = $this->baseURL . $this->baseRestAPIUrl . $this->defaultRepository . 'folders/' . $folder->id . '/move';
        $payload = [
            'destination-folder-id'     => $folder->id,
            'destination-repo'          => $destinationRepo,
            'overwrite'                 => $overwriteIfExists,
        ];
        return $this->setClient('POST', $url, $payload)->object();
    }

}
