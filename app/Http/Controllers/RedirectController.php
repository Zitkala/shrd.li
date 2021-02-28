<?php

namespace App\Http\Controllers;

use App\Models\ShortenedLink;
use App\Models\ShortenedLinkImpressions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RedirectController extends Controller
{
    protected ?ShortenedLink $link;

    public function __construct(Request $request)
    {
        $hash = md5($request->slug);

        $this->link = ShortenedLink::where('path_hash', $hash)->first();

        if (!$this->link) {
            abort(404);
        }
    }

    public function qrCode($format = '')
    {
        if (!$format) {
            abort(404);
        }

        $size = preg_replace('/\D/', '', $format);
        $size = $size>20 ? $size : 200;
        $size = $size<1500 ? $size : 1500;

        $base64 = QrCode::size($size)->format('png')->generate(config('app.url').'/'.$this->link->path);
        $image= imagecreatefromstring($base64);
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);

        exit;
    }

    public function show(Request $request)
    {
        $fields = [
            'link_id'    => $this->link->id,
            'referer'    => $request->headers->get('referer'),
            'user_ip'    => md5($request->ip()),
            'user_mac'   => md5($this->getMAcAddressExec()),
            'user_agent' => md5($request->server('HTTP_USER_AGENT')),
        ];

        $check = ShortenedLinkImpressions::where($fields)->where('created_at', '>', Carbon::now()->subHours(4))->first('id');
        if (!$check) {
            ShortenedLinkImpressions::create($fields);
            $this->link->update(['impressions' => DB::raw('impressions+1')]);
        }

        return redirect($this->link->url);
    }

    protected function getMAcAddressExec(): string
    {
        return substr(exec('getmac'), 0, 17);
    }
}
