<?php

namespace Tests\Support;

use Voyager\IOPools\Drivers\MultiCurlResourceDriver;

/**
 * The transport seam is internal to the driver, so the fake is a subclass:
 * dispatch records instead of curling, harvest hands back whatever the
 * test staged, progress answers what the test says is moving.
 */
class FakeCurlDriver extends MultiCurlResourceDriver
{
    /** @var list<array{name: string, url: string, method: string, headers: array, body: ?array}> */
    public array $dispatched = [];

    /** @var list<\Voyager\IOPools\DTO\HttpResult> */
    public array $ready = [];

    /** @var array<string, array{now: int, total: int}> */
    public array $moving = [];

    public function __construct(\Voyager\Contracts\IOPools\PoolService $io_pool)
    {
        parent::__construct([], $io_pool);
    }

    protected function dispatch(string $name, string $url, string $method, array $headers = [], ?array $body = null): void
    {
        $this->dispatched[] = compact('name', 'url', 'method', 'headers', 'body');
    }

    protected function harvest(): array
    {
        $drained = $this->ready;
        $this->ready = [];

        return $drained;
    }

    public function progress(): array
    {
        return $this->moving;
    }
}
