<?php

namespace Asseco\ContentFileStorageDriver\Responses;

class ContentItemList
{
    public int $totalCount;
    public int $pageSize;
    public int $page;
    public int $totalPages;
    public string $sortOrder;
    public string $sortBy;
    public ContentItem $items;

    public function __construct($data)
    {
        $this->totalCount = $data['total-count'] ?? 0;
        $this->pageSize = $data['$page-size'] ?? 0;
        $this->page = $data['page'] ?? 0;
        $this->totalPages = $data['total-pages'] ?? '';
        $this->sortOrder = $data['sort-order'] ?? 'asc';
        $this->sortBy = $data['sort-by'] ?? '';
        $this->items = $data['items'] ?? [];
    }
}
