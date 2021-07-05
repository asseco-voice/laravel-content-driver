<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Directory extends ContentItem
{
    public string $folderPurpose;

    private bool $isValid = true;

    public function __construct($data)
    {
        parent::__construct($data);

        $this->folderPurpose = $data['folder-purpose'] ?? '';
    }
}
