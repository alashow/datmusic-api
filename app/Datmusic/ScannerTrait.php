<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use App\Jobs\ScannerJob;
use Illuminate\Http\Request;

trait ScannerTrait
{
    public function autoScanSearchResults(Request $request, array $results, string $type)
    {
        if (config("app.search.auto_scanner.$type.enabled")) {
            $initial = config("app.search.auto_scanner.$type.initial");
            dispatch(new ScannerJob(collect($results)->take($initial)->toArray(), $type))->onQueue('auto_scan');
        }
    }
}
