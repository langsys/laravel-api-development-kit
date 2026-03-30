<?php

namespace Langsys\ApiKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeHandlerCommand extends GeneratorCommand
{
    protected $signature = 'api-kit:make-handler {name : The name of the exception handler}';
    protected $description = 'Create a new exception handler class';
    protected $type = 'Exception Handler';

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/exception-handler.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\\Exceptions\\Handlers';
    }
}
