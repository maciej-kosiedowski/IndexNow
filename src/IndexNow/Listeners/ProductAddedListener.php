<?php

namespace SlimAD\IndexNow\Listeners;

use SlimAD\IndexNow\Services\IndexNowService;

class ProductAddedListener
{
    private IndexNowService $indexNowService;

    public function __construct(IndexNowService $indexNowService)
    {
        $this->indexNowService = $indexNowService;
    }

    /**
     * Handle the event.
     * Replace "object $event" with your actual Product event class.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        // Assuming the event has a product object with a method or property to get its URL
        if (isset($event->product) && method_exists($event->product, 'getUrl')) {
            $url = $event->product->getUrl();
            $this->indexNowService->submit($url);
        }
    }
}
