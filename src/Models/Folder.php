<?php

namespace Asseco\ContentFileStorageDriver\Models;

use Asseco\ContentFileStorageDriver\Responses\Folder as FolderResponse;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

class Folder extends AbstractContent
{
    public function apiResourceName()
    {
        return 'folders';
    }

    public function responseClass(): string
    {
        return FolderResponse::class;
    }

    public function create(string $name, string $path = '/'): FolderResponse
    {
        $payload = [
            'name'           => $name,
            'path'           => $path,
            'kind'           => 'folder',
            'folder-purpose' => 'generic-folder',
        ];

        $response = $this->client->post($this->resourceUrl(), $payload)->throw()->json();

        return new FolderResponse($response);
    }

    public function createAndReturnUrl(string $path): string
    {
        $this->recursiveCreate($path);

        $folder = $this->metadataByPath(dirname($path));

        return "{$this->resourceUrl()}/{$folder->id}";
    }

    public function delete(string $folder, bool $deleteContentWithSubFolders = true): bool
    {
        $folder = $this->normalizePath($folder);

        $deleteContentWithSubFolders = $deleteContentWithSubFolders ? 'true' : 'false';

        $folderId = $this->metadataByPath($folder);

        $url = $this->resourceUrl() . '/' . $folderId->id . '?delete-content-and-subfolders=' . $deleteContentWithSubFolders;

        $request = $this->client->delete($url)->throw();

        return $request->status() === JsonResponse::HTTP_OK;
    }

    public function search(string $search): Response
    {
        $url = $this->url() . '/search?q=' . $search;

        return $this->client->get($url)->throw();
    }

    public function exists(string $path, string $basePath = '/'): bool
    {
        $url = $this->url() . $basePath . $path . '/metadata';

        try {
            $response = $this->client->get($url);
        } catch (Exception $e) {
            return false;
        }

        return $response->status() === JsonResponse::HTTP_OK;
    }

    public function recursiveCreate(string $path, string $basePath = '/')
    {
        $folders = array_filter(explode('/', dirname($this->prefix . $path)));

        foreach ($folders as $folder) {
            if (!$this->exists($folder, $basePath)) {
                $this->create($folder, $basePath);
            }

            $basePath .= "$folder/";
        }
    }

    public function listDirectory(string $folder = '', bool $recursive = false, int $perPage = 10, int $page = 0, string $order = 'asc'): Response
    {
        $folder = $this->normalizePath($folder);
        $recursive = $recursive ? 'true' : 'false';
        $url = $this->url() . '/' . $folder . '?kind=any' . '&subfolders=' . $recursive . '&page-size=' . $perPage . '&page=' . $page . '&sort-order=' . $order;

        return $this->client->get($url)->throw();
    }
}
