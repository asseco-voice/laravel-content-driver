<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class RepositoryList
{
    public array $repositories;

    public function __construct($data)
    {
        foreach($data as $item) {
            $this->repositories[] = new Repository($item);
        }

    }

    public function get(): RepositoryList
    {
        return $this;
    }
}
