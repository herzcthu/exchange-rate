<?php

namespace App\Http\Controllers;

use Goutte\Client;
use Illuminate\Http\Request;

class WebCrawlRate extends Controller
{
    public function response($service, $bank)
    {
        if($service == 'exrate') {
            return $this->$bank();
        }
    }

    public function ayar()
    {
        $client = new Client();
        $crawler = $client->request('GET', 'http://www.ayabank.com/en_US/');

        $timestamp = $crawler->filter('tr.row-1 td.column-1')->text();
        $usdbuy = $crawler->filter('tr.row-2 td.column-2')->text();
        $usdsell = $crawler->filter('tr.row-2 td.column-3')->text();

        $eubuy = $crawler->filter('tr.row-3 td.column-2')->text();
        $eusell = $crawler->filter('tr.row-3 td.column-3')->text();

        $sgdbuy = $crawler->filter('tr.row-4 td.column-2')->text();
        $sgdsell = $crawler->filter('tr.row-4 td.column-3')->text();

        $response_arr = [
            'timestamp' => $timestamp,
            'usdbuy' => $usdbuy,
            'usdsell' => $usdsell,
            'eubuy' => $eubuy,
            'eusell' => $eusell,
            'sgdbuy' => $sgdbuy,
            'sgdsell' => $sgdsell
        ];
        return \GuzzleHttp\json_encode($response_arr);
    }
}
