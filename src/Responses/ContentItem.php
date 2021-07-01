<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class ContentItem
{
    public string $id;
    public string $changedOn;
    public string $createdOn;
    public string $createdBY;
    public string $name;
    public string $path;
    public string $kind;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? '';
        $this->changedOn = $data['changed-on'] ?? '';
        $this->createdOn = $data['created-on'] ?? '';
        $this->createdBY = $data['created-by'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->path = $data['path'] ?? '';
        $this->kind = $data['kind'] ?? '';
    }

    public function get(): ContentItem
    {
        return $this;
    }
}
