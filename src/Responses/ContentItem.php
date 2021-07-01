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

    private bool $isValid = true;

    public function __construct($data)
    {
        $this->validate($data);

        $this->id = $data['id'] ?? '';
        $this->changedOn = $data['changed-on'] ?? '';
        $this->createdOn = $data['created-on'] ?? '';
        $this->createdBY = $data['created-by'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->path = $data['path'] ?? '';
        $this->kind = $data['kind'] ?? '';
    }

    public function validate($data)
    {
        if (empty($data['name'])) {
            $this->isValid = false;
        }
    }

    public function get(): ContentItem
    {
        return $this->isValid ? $this : false;
    }
}
