<?php

namespace App\Http\Controllers;

use App\Models\ShortenedLink;
use App\Models\ShortenedLinkImpressions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function show($slug, Request $request)
    {
        $hash = md5($slug);

        $url = ShortenedLink::where('path_hash', $hash)->first();

        if ($url) {
            $fields = [
                'link_id'    => $url->id,
                'referer'    => $request->headers->get('referer'),
                'user_ip'    => md5($request->ip()),
                'user_mac'   => md5($this->getMAcAddressExec()),
                'user_agent' => md5($request->server('HTTP_USER_AGENT')),
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
