<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;

class RequestHelper
{
    public static function signature(Request $request): string
    {
        $hashAlgo = 'md5';

        if (in_array('xxh3', hash_algos())) {
            $hashAlgo = 'xxh3';
        }

        return hash($hashAlgo, json_encode(
            [
                $request->ip(),
                $request->path(),
                $request->all(),
                $request->headers->all(),
            ]
        ));
    }
}
