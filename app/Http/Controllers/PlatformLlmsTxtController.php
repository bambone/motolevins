<?php

namespace App\Http\Controllers;

use App\Services\Seo\PlatformMarketingLlmsBodyRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformLlmsTxtController extends Controller
{
    public function __invoke(Request $request, PlatformMarketingLlmsBodyRenderer $renderer): Response
    {
        $body = $renderer->render($request);
        $response = new Response($body, 200);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
