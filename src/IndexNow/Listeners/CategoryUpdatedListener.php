<?php

namespace SlimAD\IndexNow\Listeners;

use SlimAD\IndexNow\Services\IndexNowService;

class CategoryUpdatedListener
{
    private IndexNowService $indexNowService;

    public function __construct(IndexNowService $indexNowService)
    {
        $this->indexNowService = $indexNowService;
    }

    /**
     * Handle the event.
     * Replace "object $event" with your actual Category event class.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        // Assuming the event has a category object with a method or property to get its URL
        if (isset($event->category) && method_exists($event->category, 'getUrl')) {
            $url = $event->category->getUrl();
            $this->indexNowService->submit($url);
        }
    }
}
