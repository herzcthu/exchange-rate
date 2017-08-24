<?php

namespace App\Http\Controllers;

use Herzcthu\ExchangeRates\CrawlBank;

class WebCrawlRate extends Controller
{
    /**
     * @param $service exrate
     * @param $bank cbm, kbz, mcb, aya, agd, cbbank
     * @param string $type sell, buy
     * @param CrawlBank $crawlbank
     * @return mixed JSON
     */
    public function response($service, $bank, $type = 'sell', CrawlBank $crawlbank)
    {
        if($service == 'exrate') {
            return $crawlbank->getRates( $bank, $type);
        }
    }
}
