<?php

namespace App\Http\Controllers;

use Herzcthu\ExchangeRates\CrawlBank;

class WebCrawlRate extends Controller
{
    public function response($service, $bank, $type = 'sell', CrawlBank $crawlbank)
    {
        if($service == 'exrate') {
            return $crawlbank->getRates( $bank, $type);
        }
    }
}
