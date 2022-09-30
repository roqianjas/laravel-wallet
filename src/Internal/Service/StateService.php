<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class StateService implements StateServiceInterface
{
    private const PREFIX_FORKS = 'wallet_forks::';

    private const PREFIX_FORK_CALL = 'wallet_fork_call::';

    private CacheRepository $forks;

    private CacheRepository $forkCallables;

    public function __construct(CacheFactory $cacheFactory)
    {
        $this->forks = $cacheFactory->store('array');
        $this->forkCallables = $cacheFactory->store('array');
    }

    public function fork(string $uuid, callable $value): void
    {
        if (! $this->forks->has(self::PREFIX_FORKS . $uuid)) {
            $this->forkCallables->put(self::PREFIX_FORK_CALL . $uuid, $value);
        }
    }

    public function get(string $uuid): ?string
    {
        $callable = $this->forkCallables->pull(self::PREFIX_FORK_CALL . $uuid);
        if ($callable !== null) {
            return $this->forks->rememberForever(self::PREFIX_FORKS . $uuid, $callable);
        }

        return $this->forks->get(self::PREFIX_FORKS . $uuid);
    }

    public function drop(string $uuid): void
    {
        $this->forkCallables->forget(self::PREFIX_FORK_CALL . $uuid);
        $this->forks->forget(self::PREFIX_FORKS . $uuid);
    }
}