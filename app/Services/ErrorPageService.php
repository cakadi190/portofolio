<?php

namespace App\Services;

class ErrorPageService
{
    /**
     * Status codes that get a dedicated Inertia error page.
     *
     * @var array<int, array{image: string}>
     */
    private const PAGES = [
        400 => ['image' => '400'],
        401 => ['image' => '403'],
        403 => ['image' => '403'],
        404 => ['image' => '404'],
        405 => ['image' => '400'],
        419 => ['image' => '400'],
        429 => ['image' => '429'],
        500 => ['image' => '500'],
        503 => ['image' => '503'],
    ];

    /**
     * Build the Inertia props for the given HTTP status, or null when the
     * status has no dedicated page.
     *
     * @return array{status: int, title: string, text: string, image: string}|null
     */
    public function propsFor(int $status): ?array
    {
        $page = self::PAGES[$status] ?? null;

        if ($page === null) {
            return null;
        }

        return [
            'status' => $status,
            'title' => __("errors.{$status}.title"),
            'text' => __("errors.{$status}.text"),
            'image' => "/images/errors/{$page['image']}.svg",
        ];
    }
}
