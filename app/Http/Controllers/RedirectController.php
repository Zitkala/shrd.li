<?php

namespace App\Http\Controllers;

use App\Models\ShortenedLink;
use App\Models\ShortenedLinkImpressions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function show($slug, Request $request)
    {
        $hash = md5($slug);

        $url = ShortenedLink::where('path_hash', $hash)->first();

        if ($url) {
            $fields = [
                'referer'   => $request->headers->get('referer'),
                'userIp'    => Crypt::encrypt($request->ip()),
                'userMac'   => Crypt::encrypt($this->getMAcAddressExec()),
                'userAgent' => Crypt::encrypt($request->server('HTTP_USER_AGENT')),
            ];

            $check = ShortenedLinkImpressions::where($fields)->where('created_at', '>', Carbon::now()->subHours(4))->first('id');
            if (!$check) {
                ShortenedLinkImpressions::create($fields);
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
