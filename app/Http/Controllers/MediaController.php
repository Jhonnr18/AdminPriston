<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class MediaController extends Controller
{
    public function icon(string $code): RedirectResponse
    {
        $code = strtoupper($code);
        if (! preg_match('/^[A-Z]{2,3}\d{2,4}$/', $code)) {
            abort(404);
        }

        return redirect('/icon.php?c='.$code, 301);
    }
}
