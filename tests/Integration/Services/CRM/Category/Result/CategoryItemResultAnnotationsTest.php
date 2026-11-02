<?php

declare(strict_types=1);

namespace Bitrix24\SDK\Tests\Integration\Services\CRM\Category\Result;

use Bitrix24\SDK\Services\CRM\Category\Result\CategoryItemResult;
use Bitrix24\SDK\Tests\CustomAssertions\CustomBitrix24Assertions;
use Bitrix24\SDK\Tests\Integration\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\TyphoonReflector;

#[CoversClass(CategoryItemResult::class)]
class CategoryItemResultAnnotationsTest extends TestCase
{
    use CustomBitrix24Assertions;

    public function testAllSystemFieldsAnnotated(): void
    {
        $category = Factory::getServiceBuilder()->getCRMScope()->category();
        $fields = $category->fields(2)->getFieldsDescription()['fields'];
        // Metadata is portal-dependent; origin fields are optional. Require every
        // live system field to be annotated, without requiring optional fields to exist.
        $collection = TyphoonReflector::build()->reflectClass(CategoryItemResult::class)->properties();
        foreach (array_keys($fields) as $field) {
            self::assertTrue(isset($collection[$field]) && $collection[$field]->isAnnotated());
        }

        foreach ($category->list(2)->getCategories() as $categoryItemResult) {
            $raw = $category->get(2, $categoryItemResult->id)->getCoreResponse()->getResponseData()->getResult()['category'];
            foreach (array_keys($raw) as $field) {
                self::assertTrue(isset($collection[$field]) && $collection[$field]->isAnnotated());
            }
        }
    }

    public function testAllSystemFieldsHasValidTypeAnnotation(): void
    {
        $category = Factory::getServiceBuilder()->getCRMScope()->category();
        $fields = $category->fields(2)->getFieldsDescription()['fields'];
        $this->assertBitrix24AllResultItemFieldsHasValidTypeAnnotation($fields, CategoryItemResult::class);
        foreach ($category->list(2)->getCategories() as $categoryItemResult) {
            $this->assertBitrix24ResultItemFieldsTypeCastMatchAnnotations($category->get(2, $categoryItemResult->id)->category(), CategoryItemResult::class);
        }
    }
}
