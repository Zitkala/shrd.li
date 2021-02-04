<?php

namespace App\Http\Controllers;

use App\Models\ShortenedLink;

class RedirectController extends Controller
{
    public function show($slug)
    {
        $hash = md5($slug);

        $url = ShortenedLink::where('path_hash', $hash)->first();

        if ($url) {
            return redirect($url->url);
        }

        abort(404);
    }
}
