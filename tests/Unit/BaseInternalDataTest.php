<?php

namespace Langsys\ApiKit\Tests\Unit;

use Langsys\ApiKit\Data\BaseInternalData;
use PHPUnit\Framework\TestCase;

class BaseInternalDataTest extends TestCase
{
    public function test_to_array_returns_public_properties(): void
    {
        $data = new class('hello', 42) extends BaseInternalData {
            public function __construct(
                public string $name,
                public int $count,
            ) {}
        };

        $array = $data->toArray();

        $this->assertEquals(['name' => 'hello', 'count' => 42], $array);
    }

    public function test_to_array_excludes_specified_keys(): void
    {
        $data = new class('hello', 42) extends BaseInternalData {
            public function __construct(
                public string $name,
                public int $count,
            ) {}
        };

        $array = $data->toArray(['count']);

        $this->assertEquals(['name' => 'hello'], $array);
    }

    public function test_to_json_returns_json_string(): void
    {
        $data = new class('test') extends BaseInternalData {
            public function __construct(
                public string $value,
            ) {}
        };

        $json = $data->toJson();

        $this->assertJson($json);
        $this->assertEquals('{"value":"test"}', $json);
    }

    public function test_backed_enum_serializes_to_value(): void
    {
        $enum = \Langsys\ApiKit\Enums\HttpCode::OK;

        $data = new class($enum) extends BaseInternalData {
            public function __construct(
                public \Langsys\ApiKit\Enums\HttpCode $code,
            ) {}
        };

        $array = $data->toArray();

        $this->assertEquals(['code' => 200], $array);
    }
}
