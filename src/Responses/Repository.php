<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class Repository
{
    public string $repositoryId;
    public string $repositoryName;

    private bool $isValid = true;

    public function __construct($data)
    {
        $this->validate($data);

        $this->repositoryId = $data['repository-id'] ?? '';
        $this->repositoryName = $data['repository-name'] ?? '';
    }

    private function validate($data)
    {
        if (empty($data)) {
            $this->isValid = false;
        }
    }

    public function get(): Repository
    {
        return $this->isValid ? $this : false;
    }
}
