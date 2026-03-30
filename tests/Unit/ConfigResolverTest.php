<?php

namespace Langsys\ApiKit\Tests\Unit;

use Langsys\ApiKit\Data\ResourceMetadata\ConfigResolver;
use Langsys\ApiKit\Tests\TestCase;

class ConfigResolverTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('api-kit.resources.UserResource', [
            'filterable' => [
                'status' => ['type' => 'string'],
                'role' => ['type' => 'string'],
            ],
            'orderable' => ['created_at', 'name'],
            'default_order' => ['created_at' => 'desc'],
            'default_filters' => ['status' => 'active'],
        ]);
    }

    public function test_get_filterable_fields(): void
    {
        $resolver = new ConfigResolver();
        $fields = $resolver->getFilterableFields('UserResource');

        $this->assertArrayHasKey('status', $fields);
        $this->assertArrayHasKey('role', $fields);
        $this->assertEquals('string', $fields['status']['type']);
    }

    public function test_get_orderable_fields(): void
    {
        $resolver = new ConfigResolver();
        $fields = $resolver->getOrderableFields('UserResource');

        $this->assertEquals(['created_at', 'name'], $fields);
    }

    public function test_get_default_order(): void
    {
        $resolver = new ConfigResolver();
        $order = $resolver->getDefaultOrder('UserResource');

        $this->assertEquals([['created_at', 'desc']], $order);
    }

    public function test_get_default_filters(): void
    {
        $resolver = new ConfigResolver();
        $filters = $resolver->getDefaultFilters('UserResource');

        $this->assertEquals(['status' => 'active'], $filters);
    }

    public function test_returns_empty_for_unknown_resource(): void
    {
        $resolver = new ConfigResolver();

        $this->assertEmpty($resolver->getFilterableFields('Unknown'));
        $this->assertEmpty($resolver->getOrderableFields('Unknown'));
        $this->assertEmpty($resolver->getDefaultOrder('Unknown'));
        $this->assertEmpty($resolver->getDefaultFilters('Unknown'));
    }
}
