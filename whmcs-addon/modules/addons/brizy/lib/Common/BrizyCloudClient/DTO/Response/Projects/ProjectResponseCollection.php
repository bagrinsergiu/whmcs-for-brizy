<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class ProjectResponseCollection implements IteratorAggregate, Countable
{
    private array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $dataArray): self
    {
        $items = [];
        foreach ($dataArray as $item) {
            $items[] = ProjectResponse::fromArray($item);
        }

        return new self($items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return array_map(fn(ProjectResponse $w) => $w->toArray(), $this->items);
    }
}
