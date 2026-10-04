<?php

namespace ProjectSaturnStudios\Stargazer;

use Closure;
use ProjectSaturnStudios\Stargazer\Enums\NasaPayload;
use ProjectSaturnStudios\Stargazer\Enums\NasaURL;
use ProjectSaturnStudios\Stargazer\Exceptions\StargazerException;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Http\Client\Factory;
use Voyager\Http\Client\PendingRequest;
use Voyager\Http\Client\Response;
use Voyager\NutsAndBolts\Collection;

class PendingNasaRequest
{
    /**
     * One hydrator serves both lanes: get() blocks and answers DTOs;
     * async() rides the event loop and answers a promise of the same DTOs.
     * $payload says what the hydrator gets: decoded JSON (a DTO class or a
     * Closure), the XML text (a Closure), or the Response (a Closure; with
     * none, the Response is the answer).
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
        protected NasaPayload $payload = NasaPayload::JSON,
        protected ?float $timeout = null,
    ) {}

    /** The same request with its own timeout in seconds; the Http client's 30 s otherwise. */
    public function timeout(float $seconds): static
    {
        $copy = clone $this;
        $copy->timeout = $seconds;

        return $copy;
    }

    public function timeoutSeconds(): ?float
    {
        return $this->timeout;
    }

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
        return $this->resolve($this->send($this->request($this->httpFactory())));
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

        return $this->send($this->request($http)->async())
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

        return match ($this->payload) {
            NasaPayload::JSON => $this->hydrate($response->json()),
            NasaPayload::XML => $this->hydrate($response->body()),
            NasaPayload::BYTES => is_null($this->hydrator) ? $response : $this->hydrate($response),
        };
    }

    /**
     * GET the URL. An empty query is left off: handed an empty one, the Http
     * client would wipe a query string already written into the path.
     */
    protected function send(PendingRequest $request): mixed
    {
        $query = $this->query();

        return $query === [] ? $request->get($this->url()) : $request->get($this->url(), $query);
    }

    /** The Http request with this one's options: its timeout, and gzip asked for on XML. */
    protected function request(Factory $http): PendingRequest
    {
        $options = [];
        if (! is_null($this->timeout)) {
            $options['timeout'] = $this->timeout;
        }
        if ($this->payload === NasaPayload::XML) {
            $options['decode_content'] = 'gzip';
        }

        return $http->withOptions($options);
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
