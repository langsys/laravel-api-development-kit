<?php

namespace Langsys\ApiKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeResourceCommand extends GeneratorCommand
{
    protected $signature = 'api-kit:make-resource {name : The name of the resource DTO}';
    protected $description = 'Create a new resource DTO class';
    protected $type = 'Resource DTO';

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/resource-dto.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Data\\Resources';
    }
}
