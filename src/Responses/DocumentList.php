<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class DocumentList
{
    public array $documents;

    public function __construct($documents)
    {
        $this->documents = $documents;
    }
}
