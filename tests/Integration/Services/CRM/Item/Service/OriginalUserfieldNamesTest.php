<?php

declare(strict_types=1);

namespace Bitrix24\SDK\Tests\Integration\Services\CRM\Item\Service;

use Bitrix24\SDK\Services\CRM\Item\Service\Item;
use Bitrix24\SDK\Tests\Integration\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Item::class)]
class OriginalUserfieldNamesTest extends TestCase
{
    public function testOriginalNamesAcrossMetadataFilteringAndMultipleBatchPages(): void
    {
        $crmServiceBuilder = Factory::getServiceBuilder()->getCRMScope();
        $type = $crmServiceBuilder->type()->add('SDK original UF read test ' . uniqid(), null, ['isCategoriesEnabled' => 'Y'])->type();
        try {
            $entityTypeId = $type->entityTypeId;
            // This compatibility line predates the typed userfieldConfig service.
            // The core call is limited to creation of the temporary test fixture.
            $field = Factory::getServiceBuilder()->core->call('userfieldconfig.add', ['moduleId' => 'crm', 'field' => [
                'entityId' => 'CRM_' . $type->id,
                'fieldName' => 'UF_CRM_' . $type->id . '_SDK_READ',
                'userTypeId' => 'string',
            ]])->getResponseData()->getResult()['field']['fieldName'];
            $fields = $crmServiceBuilder->item()->fields($entityTypeId, true)->getFieldsDescription()['fields'];
            self::assertArrayHasKey($field, $fields);
            $camelName = null;
            foreach ($crmServiceBuilder->item()->fields($entityTypeId)->getFieldsDescription()['fields'] as $name => $description) {
                if (($description['upperName'] ?? null) === $field) {
                    $camelName = $name;
                }
            }

            self::assertNotNull($camelName);
            $items = [];
            for ($i = 0; $i < 55; $i++) {
                $items[] = ['title' => 'SDK item ' . $i, $camelName => 'SDK value ' . $i];
            }

            $added = iterator_to_array($crmServiceBuilder->item()->batch->add($entityTypeId, $items));
            self::assertCount(55, $added);
            $first = $crmServiceBuilder->item()->get($entityTypeId, $added[0]->id, true)->item();
            self::assertSame('SDK value 0', $first->$field);
            $filter = ['=' . $field => 'SDK value 0'];
            self::assertSame(1, $crmServiceBuilder->item()->countByFilter($entityTypeId, $filter, true));
            self::assertCount(1, $crmServiceBuilder->item()->list($entityTypeId, [], $filter, ['id', $field], 0, true)->getItems());
            $all = iterator_to_array($crmServiceBuilder->item()->batch->list($entityTypeId, ['id' => 'ASC'], [], ['*'], null, true));
            self::assertCount(55, $all);
            self::assertCount(55, array_unique(array_map(static fn ($item): int => $item->id, $all)));
            foreach (array_values($all) as $index => $itemItemResult) {
                self::assertSame('SDK value ' . $index, $itemItemResult->__get($field));
            }

            $limited = iterator_to_array($crmServiceBuilder->item()->batch->list($entityTypeId, ['id' => 'ASC'], [], ['*'], 3, true));
            self::assertCount(3, $limited);
            $categories = $crmServiceBuilder->category()->list($entityTypeId)->getCategories();
            self::assertNotEmpty($categories);
            self::assertSame($entityTypeId, $categories[0]->entityTypeId);
        } finally {
            $ids = [];
            foreach ($crmServiceBuilder->item()->batch->list($type->entityTypeId, ['id' => 'ASC'], [], ['id']) as $itemItemResult) {
                $ids[] = $itemItemResult->id;
            }

            // The compatibility line has no typed CRM item batch-delete helper.
            foreach (array_chunk($ids, 50) as $chunk) {
                $commands = array_map(static fn (int $id): string => 'crm.item.delete?' . http_build_query(['entityTypeId' => $type->entityTypeId, 'id' => $id]), $chunk);
                $result = Factory::getServiceBuilder()->core->call('batch', ['halt' => 1, 'cmd' => $commands])->getResponseData()->getResult();
                self::assertSame([], $result['result_error']);
                self::assertCount(count($chunk), $result['result']);
            }

            self::assertTrue($crmServiceBuilder->type()->delete($type->id)->isSuccess());
            self::assertSame([], $crmServiceBuilder->type()->list(filter: ['id' => $type->id])->getTypes());
        }
    }
}
