<?php

namespace ProjectSaturnStudios\Stargazer;

use Closure;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\Http\Client\Response;
use Voyager\NutsAndBolts\Collection;

class PendingNasaRequest
{
    /**
     * One hydrator serves both lanes: get() blocks and answers DTOs;
     * async() rides the event loop and answers a promise of the same DTOs.
     *
     * @param  array<string, mixed>  $query
     * @param  Closure(mixed):mixed|class-string|null  $hydrator
     */
    public function __construct(
        protected NasaURL $base,
        protected string $path,
        protected string $call_name,
        protected Closure|string|null $hydrator = null,
        protected array $query = [],
        protected ?string $api_key = null,
        protected ?Factory $http = null,
    ) {}

    public function with(string $name, mixed $value): static
    {
        $copy = clone $this;

        if (is_null($value)) {
            unset($copy->query[$name]);
        } else {
            $copy->query[$name] = $value;
        }

        return $copy;
    }

    public function __call(string $name, array $arguments): static
    {
        return $this->with($name, $arguments[0] ?? null);
    }

    public function get(): mixed
    {
        return $this->resolve($this->httpFactory()->get($this->url(), $this->query()));
    }

    /**
     * Send on the event loop. The promise fulfils with what get() would
     * have returned and rejects with what get() would have thrown.
     */
    public function async(): Promise
    {
        $http = $this->httpFactory();

        if (is_null($http->loop())) {
            throw StargazerException::loopNotBound();
        }

        return $http->async()->get($this->url(), $this->query())
            ->then(fn (Response $response): mixed => $this->resolve($response));
    }

    protected function resolve(Response $response): mixed
    {
        if (! $response->successful()) {
            throw StargazerException::requestFailed(
                status: $response->status(),
                url: $this->url(),
                body: $response->body(),
            );
        }

        return $this->hydrate($response->json());
    }

    public function url(): string
    {
        $path = ltrim($this->path, '/');

        // A pathless request keeps the base verbatim — some hosts 404
        // without their declared trailing slash (InSight).
        if ($path === '') {
            return $this->base->value;
        }

        return rtrim($this->base->value, '/').'/'.$path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $query = [];

        foreach ($this->query as $name => $value) {
            if ($value instanceof \BackedEnum) {
                $query[$name] = $value->value;
            } elseif (is_bool($value)) {
                $query[$name] = $value ? 'true' : 'false';
            } else {
                $query[$name] = $value;
            }
        }

        if ($this->requiresApiKey()) {
            $key = $this->api_key;
            if (is_null($key) || $key === '') {
                $key = $this->resolveApiKey();
            }
            $query['api_key'] = $key;
        }

        return $query;
    }

    public function callName(): string
    {
        return $this->call_name;
    }

    protected function requiresApiKey(): bool
    {
        return parse_url($this->base->value, PHP_URL_HOST) === 'api.nasa.gov';
    }

    protected function resolveApiKey(): string
    {
        if (function_exists('app') && app()->bound('config')) {
            $key = app('config')->get('nasa.api_key');
            if (! is_null($key) && $key !== '') {
                return (string) $key;
            }
        }

        return 'DEMO_KEY';
    }

    protected function httpFactory(): Factory
    {
        if (! is_null($this->http)) {
            return $this->http;
        }

        if (function_exists('app') && app()->bound('http')) {
            return app('http');
        }

        throw StargazerException::httpClientUnavailable();
    }

    protected function hydrate(mixed $payload): mixed
    {
        if ($this->hydrator instanceof Closure) {
            return ($this->hydrator)($payload);
        }

        $dto = $this->hydrator;

        if (! is_string($dto) || ! class_exists($dto) || ! method_exists($dto, 'fromArray')) {
            throw StargazerException::invalidHydrator($dto);
        }

        if (! is_array($payload)) {
            throw StargazerException::invalidPayload($this->url());
        }

        if (array_is_list($payload)) {
            return Collection::make($payload)->map(
                fn (mixed $row) => $dto::fromArray((array) $row),
            );
        }

        return $dto::fromArray($payload);
    }
}
