<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Directory extends ContentItem
{
    public string $folderPurpose;

    private bool $isValid = true;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->validate($data);

        $this->folderPurpose = $data['folder-purpose'] ?? '';

    }

    public function get(): Directory
    {
        return $this->isValid ? $this : false;
    }
}
