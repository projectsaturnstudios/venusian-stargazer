<?php

namespace ProjectSaturnStudios\Stargazer;

use Closure;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use Voyager\Contracts\IOPools\Completion;
use Voyager\Contracts\IOPools\PoolService;
use Voyager\Http\Client\Factory;
use Voyager\IOPools\DTO\HttpResult;
use Voyager\IOPools\Presumption;
use Voyager\MagicAliases\MagicAlias;
use Voyager\NutsAndBolts\Collection;
use Voyager\NutsAndBolts\MagicAliases\Http;

class PendingNasaRequest
{
    /**
     * $hydrator shapes the sync lane: get() feeds it the decoded JSON and
     * answers DTOs. $envelope shapes the async lane: the driver feeds it
     * the HttpResult and the mail it answers rides the dock. Without an
     * envelope, async() mails the raw HttpResult.
     *
     * @param  array<string, mixed>  $query
     * @param  Closure(mixed):mixed|class-string|null  $hydrator
     * @param  Closure(HttpResult):Completion|null  $envelope
     */
    public function __construct(
        protected NasaURL $base,
        protected string $path,
        protected string $call_name,
        protected Closure|string|null $hydrator = null,
        protected array $query = [],
        protected ?string $api_key = null,
        protected ?Factory $http = null,
        protected ?PoolService $io_pool = null,
        protected ?Closure $envelope = null,
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
        $response = $this->httpFactory()->get($this->url(), $this->query());

        if (! $response->successful()) {
            throw StargazerException::requestFailed(
                status: $response->status(),
                url: $this->url(),
                body: $response->body(),
            );
        }

        return $this->hydrate($response->json());
    }

    public function async(): Presumption
    {
        return $this->resolvePool()->http()->call(
            name: $this->call_name,
            url: $this->absoluteUrl(),
            method: 'GET',
            envelope: $this->envelope,
        );
    }

    public function url(): string
    {
        $base = rtrim($this->base->value, '/');
        $path = ltrim($this->path, '/');

        if ($path === '') {
            return $base;
        }

        return $base.'/'.$path;
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

    protected function absoluteUrl(): string
    {
        $url = $this->url();
        $query = $this->query();

        if ($query === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($query);
    }

    protected function requiresApiKey(): bool
    {
        return parse_url($this->base->value, PHP_URL_HOST) === 'api.nasa.gov';
    }

    protected function resolveApiKey(): string
    {
        $vessel = MagicAlias::getMagicAliasApplication();

        if (! is_null($vessel) && $vessel->bound('config')) {
            $key = $vessel->make('config')->get('nasa.api_key');
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

        $root = Http::getMagicAliasRoot();
        if ($root instanceof Factory) {
            return $root;
        }

        throw StargazerException::httpClientUnavailable();
    }

    protected function resolvePool(): PoolService
    {
        if (! is_null($this->io_pool)) {
            return $this->io_pool;
        }

        $vessel = MagicAlias::getMagicAliasApplication();
        if (! is_null($vessel) && $vessel->bound(HttpPool::class)) {
            return $vessel->make(HttpPool::class);
        }

        if (function_exists('app')) {
            try {
                $app = app();
                if (! is_null($app) && $app->bound(HttpPool::class)) {
                    return $app->make(HttpPool::class);
                }
            } catch (\Throwable) {
            }
        }

        throw StargazerException::httpPoolNotBound();
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
