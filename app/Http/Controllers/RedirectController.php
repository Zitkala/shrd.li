<?php

namespace App\Http\Controllers;

use App\Models\ShortenedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class RedirectController extends Controller
{
    public function show($slug, Request $request)
    {
        $hash = md5($slug);

        $url = ShortenedLink::where('path_hash', $hash)->first();

        if ($url) {
            $referer = $request->headers->get('referer');
            $userIp = $request->ip();
            $userMac = $this->getMAcAddressExec();
            $userAgent = $request->server('HTTP_USER_AGENT');

            $key = md5($referer.$url->path).'-'.md5($userIp.$userMac.$userAgent);
            if (!Cache::has($key)) {
                Cache::put('ref-'.$key, Date::now()->toString(), 3600);
                $url->update(['impressions' => DB::raw('impressions+1')]);
            }

            return redirect($url->url);
        }

        abort(404);
    }

    protected function getMAcAddressExec(): string
    {
        return substr(exec('getmac'), 0, 17);
    }
}
