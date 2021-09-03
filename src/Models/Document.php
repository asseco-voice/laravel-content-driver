<?php

namespace Asseco\ContentFileStorageDriver\Models;

use Asseco\ContentFileStorageDriver\Responses\Document as DocumentResponse;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class Document extends AbstractContent
{
    public function apiResourceName()
    {
        return 'documents';
    }

    public function responseClass(): string
    {
        return DocumentResponse::class;
    }

    public function upload(string $url, string $path, $contents, bool $overwrite = false): DocumentResponse
    {
        $path = $this->normalizePath($path);
        $filename = basename($path);

        $purpose = trim($this->prefix, '/');

        $mimeTypeDetector = new ExtensionMimeTypeDetector();
        $mediaType = $mimeTypeDetector->detectMimeTypeFromPath($path);

        $payload = [
            'content-stream'      => $contents,
            'name'                => $filename,
            'media-type'          => $mediaType,
            'filing-purpose'      => $purpose ?? 'service',
            'filing-case-number'  => 'record id',
            'overwrite-if-exists' => $overwrite ? 'true' : 'false',
        ];

        try {
            $response = $this->client
                ->attach('content-stream', $contents, $filename)
                ->post($url, $payload)
                ->throw();

            return new DocumentResponse($response->json());
        } catch (Exception $e) {
            Log::error("Couldn't get response for path '{$path}': " . print_r($e->getMessage(), true));
            Log::error('Filename: ' . print_r($filename, true));
            Log::error('Contents: ' . print_r($contents, true));
            throw new Exception($e->getMessage());
        }
    }

    public function uploadStream(string $path, $resource, bool $overwrite = false): DocumentResponse
    {
        if (!is_resource($resource)) {
            throw new Exception(sprintf('Argument must be a valid resource type. %s given.', gettype($resource)));
        }

        return $this->upload($path, stream_get_contents($resource), $overwrite);
    }

    public function get(string $filename = '/'): Response
    {
        $filenameId = $this->metadataByPath($filename);

        $url = "{$this->resourceUrl()}/{$filenameId->id}";

        return $this->client->get($url)->throw();
    }

    public function getStream(string $filename = '/')
    {
        $filename = $this->normalizePath($filename);

        $filenameId = $this->metadataByPath($filename);
        $url = $this->url() . '/documents/' . $filenameId->id;
        $content = $this->client->get($url)->throw()->body();

        // save it to temporary dir first.
        $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
        file_put_contents($tmpFilePath, $content);

        return fopen($tmpFilePath, 'rb');
    }

    public function moveFile(string $sourceFile, string $destinationFolder, string $destinationRepo = null, bool $overwriteIfExists = true): bool
    {
        $sourceFile = $this->metadataByPath($this->normalizePath($sourceFile));
        $destinationFolder = $this->folderMetadata($this->normalizePath($destinationFolder));
        $url = $this->url() . '/documents/' . $sourceFile->id . '/move';
        $payload = [
            'destination-folder-id' => $destinationFolder->id,
            'destination-repo'      => $destinationRepo ?? $this->repository,
            'overwrite'             => $overwriteIfExists,
        ];
        $request = $this->client->post($url, $payload)->throw();

        return in_array($request->status(), [JsonResponse::HTTP_OK, JsonResponse::HTTP_NO_CONTENT]);
    }

    public function delete(string $path): Response
    {
        $path = $this->normalizePath($path);
        $filenameId = $this->metadataByPath($path);
        $url = $this->url() . '/documents/' . $filenameId->id;

        return $this->client->delete($url)->throw();
    }
}
