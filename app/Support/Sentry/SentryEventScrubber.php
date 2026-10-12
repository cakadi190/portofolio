<?php

namespace App\Support\Sentry;

use App\Support\ActivitySanitizer;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Logs\Log;
use Sentry\UserDataBag;

/**
 * `before_send*` hooks (array callables so `config:cache` keeps working) that
 * remove personal data from every event, transaction, breadcrumb and log.
 */
final class SentryEventScrubber
{
    /** Request headers that are safe and useful for debugging. */
    private const array ALLOWED_HEADERS = ['user-agent', 'content-type', 'accept', 'accept-language', 'x-inertia', 'x-inertia-version', 'x-request-id', 'referer'];

    public static function beforeSend(Event $event, ?EventHint $hint = null): ?Event
    {
        $request = $event->getRequest();

        if ($request !== []) {
            unset($request['cookies'], $request['data'], $request['env']);

            if (isset($request['url']) && is_string($request['url'])) {
                $request['url'] = PiiRedactor::url($request['url']);
            }

            if (isset($request['query_string'])) {
                $request['query_string'] = is_string($request['query_string'])
                    ? PiiRedactor::url('?'.$request['query_string']) : PiiRedactor::value($request['query_string']);
                $request['query_string'] = ltrim((string) $request['query_string'], '?');
            }

            $request['headers'] = array_filter(
                (array) ($request['headers'] ?? []),
                static fn ($key): bool => in_array(strtolower((string) $key), self::ALLOWED_HEADERS, true),
                ARRAY_FILTER_USE_KEY,
            );

            if (isset($request['headers']['referer'])) {
                $request['headers']['referer'] = PiiRedactor::url(self::first($request['headers']['referer']));
            }

            $event->setRequest($request);
        }

        $user = $event->getUser();
        $event->setUser($user?->getId() !== null ? UserDataBag::createFromUserIdentifier((string) $user->getId())->setSegment($user->getSegment()) : null);

        if (($message = $event->getMessage()) !== null) {
            $event->setMessage(PiiRedactor::string($message), array_map(static fn ($p) => PiiRedactor::value($p), $event->getMessageParams()));
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(PiiRedactor::string($exception->getValue()));
        }

        $event->setExtra(PiiRedactor::value($event->getExtra()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, PiiRedactor::value($context));
        }

        $event->setBreadcrumb(array_filter(array_map(static fn (Breadcrumb $crumb): ?Breadcrumb => self::beforeBreadcrumb($crumb), $event->getBreadcrumbs())));

        return $event;
    }

    public static function beforeBreadcrumb(Breadcrumb $crumb): ?Breadcrumb
    {
        $clean = $crumb->withMessage(PiiRedactor::string((string) $crumb->getMessage()));

        foreach ($crumb->getMetadata() as $name => $value) {
            $clean = $clean->withMetadata((string) $name, is_string($name) && ActivitySanitizer::isSensitiveKey($name)
                ? PiiRedactor::FILTERED : PiiRedactor::value($value));
        }

        return $clean;
    }

    public static function beforeSendLog(Log $log): ?Log
    {
        $log->setBody(PiiRedactor::string($log->getBody()));

        foreach ($log->attributes()->toSimpleArray() as $key => $value) {
            $log->setAttribute((string) $key, ActivitySanitizer::isSensitiveKey((string) $key)
                ? PiiRedactor::FILTERED : PiiRedactor::value($value));
        }

        return $log;
    }

    private static function first(mixed $value): string
    {
        return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
    }
}
