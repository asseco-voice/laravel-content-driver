<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Document extends ContentItem
{
    public string $mediaType;
    public string $filingPurpose;
    public string $filingCaseNumber;
    public int $sizeInBytes;

    private bool $isValid = true;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->validate($data);

        $this->kind = $data['kind'] ?? '';
        $this->mediaType = $data['media-type'] ?? '';
        $this->filingPurpose = $data['filing-purpose'] ?? '';
        $this->filingCaseNumber = $data['filing-case-number'] ?? '';
        $this->sizeInBytes = $data['size-in-bytes'] ?? 0;
    }

    public function get(): Document
    {
        return $this->isValid ? $this : false;
    }
}