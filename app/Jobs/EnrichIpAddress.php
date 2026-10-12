<?php

namespace App\Jobs;

use App\Services\Visitors\IpInfoClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Enrich one IP address off the request path. Unique per IP so a traffic
 * spike cannot queue the same lookup repeatedly.
 */
class EnrichIpAddress implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $uniqueFor = 300;

    public function __construct(public readonly string $ipAddress) {}

    public function uniqueId(): string
    {
        return $this->ipAddress;
    }

    public function handle(IpInfoClient $client): void
    {
        $client->lookup($this->ipAddress);
    }
}
