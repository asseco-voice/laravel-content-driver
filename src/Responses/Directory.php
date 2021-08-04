<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Directory extends ContentItem
{
    public string $folderPurpose;

    public function __construct($data)
    {
        parent::__construct($data);

        $this->folderPurpose = $data['folder-purpose'] ?? '';
    }
}
