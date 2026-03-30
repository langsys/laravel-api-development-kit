<?php

namespace Langsys\ApiKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeRequestCommand extends GeneratorCommand
{
    protected $signature = 'api-kit:make-request {name : The name of the request DTO}';
    protected $description = 'Create a new request DTO class';
    protected $type = 'Request DTO';

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/request-dto.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Data\\Requests';
    }
}
