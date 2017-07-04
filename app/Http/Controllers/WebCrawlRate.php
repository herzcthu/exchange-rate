<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\DomCrawler\Crawler;

class WebCrawlRate extends Controller
{
    public function response($service, $bank, $type = 'sell')
    {
        if($service == 'exrate') {
            return $this->$bank($type);
        }
    }

    private function cbm($type) {
        $content = file_get_contents('http://forex.cbm.gov.mm/api/latest');
        $base_info = [
            'status' => 'Success',
            'type' => 'CBM'
        ];
        $cbm_rate = json_decode($content, true);
        $response = array_merge($base_info, $cbm_rate);
        return Response::json($response);
    }

    private function kbz($type) {
        $client = new Client();
        $crawler = $client->request('GET', 'https://www.kbzbank.com/en/');
        $exrate = $crawler->filter('div.row.exchange-rate div')->children()->each(function (Crawler $node, $i) {

            $response = [];

            if (strpos($node->filter('span')->text(), 'EXCHANGE') !== false) {
                $response['timestamp'] = $node->filter('strong')->text();
            }

            if (strpos($node->filter('span')->text(), 'USD') !== false) {
                preg_match('/(?<=BUY\s)([0-9]+)/',$node->text(), $buy);
                $response['buy']['USD'] = $buy[1];

                preg_match('/(?<=SELL\s)([0-9]+)/',$node->text(), $sell);
                $response['sell']['USD'] = $sell[1];
            }

            if (strpos($node->filter('span')->text(), 'SGD') !== false) {
                preg_match('/(?<=BUY\s)([0-9]+)/',$node->text(), $buy);
                $response['buy']['SGD'] = $buy[1];

                preg_match('/(?<=SELL\s)([0-9]+)/',$node->text(), $sell);
                $response['sell']['SGD'] = $sell[1];
            }

            if (strpos($node->filter('span')->text(), 'EUR') !== false) {
                preg_match('/(?<=BUY\s)([0-9]+)/',$node->text(), $buy);
                $response['buy']['EUR'] = $buy[1];

                preg_match('/(?<=SELL\s)([0-9]+)/',$node->text(), $sell);
                $response['sell']['EUR'] = $sell[1];
            }

            if (strpos($node->filter('span')->text(), 'THB') !== false) {
                preg_match('/(?<=BUY\s)([0-9]+)/',$node->text(), $buy);
                $response['buy']['THB'] = $buy[1];

                preg_match('/(?<=SELL\s)([0-9]+)/',$node->text(), $sell);
                $response['sell']['THB'] = $sell[1];
            }

            return $response;

        });

        $base_info = [
            'status' => 'Success',
            'type' => strtoupper($type),
            'info' => 'KBZ Bank Exchange Rate',
            'description' => 'KBZ Bank Exchange Rate extracted from kbzbank.com',
            'timestamp' => strtotime($exrate[0]['timestamp'])
        ];

        $sell_rates = [];

        $buy_rates = [];

        $sell = array_column($exrate,'sell');
        foreach ($sell as $rates) {
            foreach($rates as $currency => $rate) {
                $sell_rates['rates'][$currency] = $rate;
            }
        }

        $buy = array_column($exrate,'buy');
        foreach ($buy as $rates) {
            foreach($rates as $currency => $rate) {
                $buy_rates['rates'][$currency] = $rate;
            }
        }


        $rate = $type.'_rates';
        $response = array_merge($base_info, $$rate);
        return Response::json($response);
    }

    private function aya($type)
    {
        $client = new Client();
        $crawler = $client->request('GET', 'http://www.ayabank.com/en_US/');
        $timestamp = $crawler->filter('tr.row-1 td.column-1')->text();

        $timestamp = strtotime(preg_replace('/[^0-9a-zA-Z:]\s?/s', " ", $timestamp));

        $usdbuy = $crawler->filter('tr.row-2 td.column-2')->text();
        $usdsell = $crawler->filter('tr.row-2 td.column-3')->text();

        $eubuy = $crawler->filter('tr.row-3 td.column-2')->text();
        $eusell = $crawler->filter('tr.row-3 td.column-3')->text();

        $sgdbuy = $crawler->filter('tr.row-4 td.column-2')->text();
        $sgdsell = $crawler->filter('tr.row-4 td.column-3')->text();
        $base_info = [
            'status' => 'Success',
            'type' => strtoupper($type),
            'info' => 'Aya Bank Exchange Rate',
            'description' => 'Aya Bank Exchange Rate extracted from ayabank.com',
            'timestamp' => $timestamp
        ];

        $sell_rates['rates'] = [
            'USD' => $usdsell,
            'EUR' => $eusell,
            'SGD' => $sgdsell
        ];

        $buy_rates['rates'] = [
            'USD' => $usdbuy,
            'EUR' => $eubuy,
            'SGD' => $sgdbuy
        ];
        $rate = $type.'_rates';
        $response = array_merge($base_info, $$rate);
        return Response::json($response);
    }

    private function agd($type) {
        $content = file_get_contents('http://otcservice.agdbank.com.mm/utility/rateinfo?callback=?');
        $agdrates = json_decode(substr($content, 2, -2));
        $base_info = [
            'status' => 'Success',
            'type' => strtoupper($type),
            'info' => 'AGD Bank Exchange Rate',
            'description' => 'AGD Bank Exchange Rate extracted from agdbank.com.mm',
            'timestamp' => false
        ];

        $sell_rates = [];
        $buy_rates = [];
        foreach ($agdrates->ExchangeRates as $rates) {
            if($rates->From == 'KYT') {
                $buy_rates['rates'][$rates->To] = $rates->Rate;
            }
            if($rates->To == 'KYT') {
                $sell_rates['rates'][$rates->From] = $rates->Rate;
            }
        }

        $rate = $type.'_rates';
        $response = array_merge($base_info, $$rate);
        return Response::json($response);
    }


    private function cbbank($type)
    {
        $client = new Client();
        $crawler = $client->request('GET', 'http://www.cbbank.com.mm/exchange_rate.aspx');

        $timestamp = $crawler->filter('tr:nth-child(7)')->text();
        $timestamp = strtotime(preg_replace('/[^0-9a-zA-Z]\s+/S', " ", $timestamp));

        $usdbuy = $crawler->filter('tr:nth-child(2) td:nth-child(2)')->text();

        $usdsell = $crawler->filter('tr:nth-child(2) td:nth-child(3)')->text();

        $eubuy = $crawler->filter('tr:nth-child(3) td:nth-child(2)')->text();
        $eusell = $crawler->filter('tr:nth-child(3) td:nth-child(3)')->text();

        $sgdbuy = $crawler->filter('tr:nth-child(4) td:nth-child(2)')->text();
        $sgdsell = $crawler->filter('tr:nth-child(4) td:nth-child(3)')->text();

        $base_info = [
            'status' => 'Success',
            'type' => strtoupper($type),
            'info' => 'CB Bank Exchange Rate',
            'description' => 'CB Bank Exchange Rate extracted from cbbank.com.mm',
            'timestamp' => $timestamp
        ];

        $sell_rates['rates'] = [
            'USD' => $usdsell,
            'EUR' => $eusell,
            'SGD' => $sgdsell
        ];

        $buy_rates['rates'] = [
            'USD' => $usdbuy,
            'EUR' => $eubuy,
            'SGD' => $sgdbuy
        ];
        $rate = $type.'_rates';
        $response = array_merge($base_info, $$rate);
        return Response::json($response);
    }

    public function __call($method, $parameters)
    {
        $response['status'] = 'Error';
        $response['message'] = 'Method ('.$method.') not defined!';
        return Response::json($response);
    }
}
