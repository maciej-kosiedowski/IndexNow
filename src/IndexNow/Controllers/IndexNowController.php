<?php

namespace SlimAD\IndexNow\Controllers;

use Illuminate\Http\Response;

class IndexNowController
{
    /**
     * Endpoint for the search engines to verify the key.
     * Returns a text response with the key.
     *
     * @param string $key
     * @return Response
     */
    public function keyLocation(string $key): Response
    {
        $keys = config('indexnow.keys', []);

        if (in_array($key, array_values($keys))) {
            return response($key, 200)
                  ->header('Content-Type', 'text/plain');
        }

        return response('Key not found', 404)
              ->header('Content-Type', 'text/plain');
    }
}
