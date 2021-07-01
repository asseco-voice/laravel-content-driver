<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Repository
{
    public string $repositoryId;
    public string $repositoryName;

    public function __construct($data)
    {
        $this->validate($data);

        $this->repositoryId = $data['repository-id'] ?? '';
        $this->repositoryName = $data['repository-name'] ?? '';
    }

}
