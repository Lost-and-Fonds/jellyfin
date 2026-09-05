<?php

declare(strict_types=1);

namespace Jellyfin;

use RuntimeException;
use Stashd\PluginSdk as Sdk;
use Uri\Rfc3986\Uri;

final class JellyfinBroadcast implements Sdk\BroadcastPlugin
{
    public function prepare(Sdk\PublishRequest $request): Sdk\Preparation
    {
        return new Sdk\Preparation();
    }

    public function publish(Sdk\PublishRequest $request): Sdk\Publication
    {
        $files = [];

        foreach ($request->items as $item) {
            $resource = $this->videoResource($item);

            if ($resource === null) {
                continue;
            }
            $index = $this->itemIndex($item, $request->items);
            $season = $this->season($request, $item->sourceReference);
            $files[] = new Sdk\PublishedFile(
                $item->id,
                $resource->reference,
                sprintf('Season %02d/S%02dE%02d - %s.mp4', $season, $season, $index, $this->sanitize($item->title)),
            );
        }

        return new Sdk\Publication(new Sdk\Artifact(''), $files);
    }

    public function finalize(Sdk\FinalizationRequest $request, Sdk\PluginContext $context): Sdk\Publication
    {
        $server = $this->setting($request->request->settings, 'server_url');

        if ($server === null) {
            throw new RuntimeException('Jellyfin server URL is not configured');
        }
        $response = $context->http->request('POST', $this->endpoint($server, '/Library/Refresh'), [], null, $this->credential($request->request->settings));
        $this->requireSuccess($response->status, 'Jellyfin refresh');
        $context->progress->report('remote refresh complete');

        return $request->publication;
    }

    public function operation(Sdk\OperationRequest $request, Sdk\PluginContext $context): Sdk\OperationResult
    {
        $server = $this->setting($request->settings, 'server_url');

        if ($server === null) {
            throw new RuntimeException('Jellyfin server URL is not configured');
        }
        $path = match ($request->name) {
            'test-connection' => '/System/Info/Public',
            'discover-libraries' => '/Library/MediaFolders',
            'refresh-library' => '/Library/Refresh',
            default => throw new RuntimeException('Unsupported external operation'),
        };
        $method = $request->name === 'refresh-library' ? 'POST' : 'GET';
        $response = $context->http->request($method, $this->endpoint($server, $path), [], null, $this->credential($request->settings));
        $this->requireSuccess($response->status, 'Jellyfin request');

        if ($request->name === 'refresh-library') {
            return new Sdk\OperationResult(values: [new Sdk\Setting('ok', Sdk\OptionValue::text('true'))]);
        }

        try {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Jellyfin returned invalid JSON', 0, $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('Jellyfin returned invalid JSON');
        }

        if ($request->name === 'test-connection') {
            $serverName = is_string($data['ServerName'] ?? null) ? $data['ServerName'] : 'Jellyfin';
            $version = is_string($data['Version'] ?? null) ? $data['Version'] : '';

            return new Sdk\OperationResult(values: [
                new Sdk\Setting('ok', Sdk\OptionValue::text('true')),
                new Sdk\Setting('message', Sdk\OptionValue::text('Jellyfin connection OK.')),
                new Sdk\Setting('server_name', Sdk\OptionValue::text($serverName)),
                new Sdk\Setting('version', Sdk\OptionValue::text($version)),
            ]);
        }
        $choices = [];

        foreach (is_array($data['Items'] ?? null) ? $data['Items'] : [] as $item) {
            if (is_array($item) && is_string($item['Id'] ?? null)) {
                $label = is_string($item['Name'] ?? null) ? $item['Name'] : 'Library';
                $choices[] = new Sdk\Choice($item['Id'], $label);
            }
        }

        return new Sdk\OperationResult($choices);
    }

    /** @param list<Sdk\Setting> $settings */
    private function setting(array $settings, string $key): ?string
    {
        foreach ($settings as $setting) {
            if ($setting->key === $key && $setting->value->kind === 'text') {
                return (string) $setting->value->value;
            }
        }

        return null;
    }

    /** @param list<Sdk\Setting> $settings */
    private function credential(array $settings): string
    {
        return $this->setting($settings, 'credential_name') ?? 'jellyfin-api-token';
    }

    private function requireSuccess(int $status, string $operation): void
    {
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException($operation . ' returned HTTP ' . $status);
        }
    }

    private function endpoint(string $server, string $path): string
    {
        $uri = Uri::parse(rtrim($server, '/') . '/' . ltrim($path, '/'));

        return $uri?->toString() ?? throw new RuntimeException('Jellyfin server URL is invalid');
    }

    private function videoResource(Sdk\Item $item): ?Sdk\ItemResource
    {
        foreach ($item->resources as $resource) {
            if ($resource->kind === 'video') {
                return $resource;
            }
        }

        return null;
    }

    /** @param list<Sdk\Item> $items */
    private function itemIndex(Sdk\Item $item, array $items): int
    {
        foreach ($items as $index => $candidate) {
            if ($candidate->id === $item->id) {
                return $index + 1;
            }
        }

        return 1;
    }

    private function season(Sdk\PublishRequest $request, ?string $sourceReference): int
    {
        foreach ($request->sources as $source) {
            if ($source->reference !== $sourceReference) {
                continue;
            }

            foreach ($source->settings as $setting) {
                if ($setting->key === 'season' && $setting->value->kind === 'number') {
                    return max(1, (int) $setting->value->value);
                }
            }
        }

        return 1;
    }

    private function sanitize(string $value): string
    {
        $value = strtr($value, ['/' => '_', '\\' => '_', ':' => '_', '*' => '_', '?' => '_', '"' => '_', '<' => '_', '>' => '_', '|' => '_']);

        return substr(trim($value, " .\t\n\r\0\x0B"), 0, 180);
    }
}
