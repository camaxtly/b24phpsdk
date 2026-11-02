<?php

declare(strict_types=1);

namespace Bitrix24\SDK\Services\CRM\Category\Result;

use Bitrix24\SDK\Core\Result\AbstractItem;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $sort
 * @property-read int $entityTypeId
 * @property-read bool $isDefault
 * @property-read string|null $originId
 * @property-read string|null $originatorId
 */
class CategoryItemResult extends AbstractItem
{
    /**
     * @param int|string $offset
     */
    #[\Override]
    public function __get($offset): mixed
    {
        $value = parent::__get($offset);
        if ($value === null) {
            return null;
        }

        return match ($offset) {
            'id', 'sort', 'entityTypeId' => (int) $value,
            'isDefault' => in_array($value, [true, 1, '1', 'Y', 'y'], true),
            'name', 'originId', 'originatorId' => (string) $value,
            default => $value,
        };
    }
}
