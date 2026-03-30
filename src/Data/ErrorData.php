<?php

namespace Langsys\ApiKit\Data;

class ErrorData extends BaseInternalData
{
    public function __construct(
        public string $message,
        public string $file,
        public int $line,
        public ?string $appName = null,
        public ?string $url = null,
        public ?string $method = null,
        public ?string $payload = null,
        public ?int $createdAt = null,
    ) {
        $this->appName ??= config('app.name', 'Laravel');
        $this->url ??= request()?->url();
        $this->method ??= request()?->method();
        $this->payload ??= json_encode(request()?->all() ?? []);
        $this->createdAt ??= now()->timestamp;
    }

    public function signature(): string
    {
        $normalizedMessage = preg_replace('/\(Connection:.*$/s', '', $this->message);
        $normalizedMessage = preg_replace('/\(SQL:.*$/s', '', $normalizedMessage);
        $normalizedMessage = preg_replace([
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            '/\$\d+/',
        ], '', $normalizedMessage);

        return md5($normalizedMessage . $this->file . $this->line);
    }
}
