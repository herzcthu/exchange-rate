<?php
/**
 * Created by PhpStorm.
 * User: sithu
 * Date: 9/24/17
 * Time: 7:32 PM
 */

namespace App\Traits;


use BotMan\BotMan\BotMan;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Support\Facades\Log;

trait ExBotTrait
{
    private $symbol = [
        'EURO' => '€',
        'EUR' => '€',
        'USD' => '$',
        'THB' => '฿',
        'SGD' => 'S$',
        'MYR' => 'K',
    ];

    private function currencyResponseWeb(BotMan $bot, $match, CrawlBank $crawlBank, $nocache = false) {

        switch ($match) {
            case 'eur':
            case 'euro':
                $currency = 'eur';
                break;
            default:
                $currency = $match;
        }

        $rates = $this->get_exrate($currency, $bot, $crawlBank, $nocache);

        Log::info($rates);

        $currency = strtoupper($currency);

        foreach ($rates as $bank => $bank_rates) {

            $reply = "\n" . $currency . ' rate for ' . $bank;
            foreach ($bank_rates as $type => $rate) {
                $reply .= "\t" . $type . ' : ' . $rate . "\n";
            }
            $bot->reply($reply);
        }

    }

    /**
     * @param BotMan $bot
     * @param $match
     * @param $nocache
     * @param $crawlBank
     */
    private function bankResponseWeb(BotMan $bot, $match, CrawlBank $crawlBank, $nocache=false)
    {
        switch ($match) {
            case 'cb':
            case 'cbbank':
                $bank = 'cbbank';
                break;
            default:
                $bank = $match;
        }

        $rates = $this->get_bankrate($bank, $bot, $crawlBank, $nocache);

        Log::info($rates);

        $reply = str_replace(' ', '  ', $rates['info']) . ' ';

        $exrates = [];

        foreach ($rates['sell_rates'] as $currency => $rate) {
            $exrates[$this->symbol[$currency] . '  ' . $currency . '  (SELL)'] = $rate;
        }

        foreach ($rates['buy_rates'] as $currency => $rate) {
            $exrates[$this->symbol[$currency] . '  ' . $currency . '  (BUY)'] = $rate;
        }

        $reply_rates = array_sort_recursive($exrates);

        foreach ($reply_rates as $currency => $rate) {
            $reply .= $currency . '  :  ' . $rate . "               
                \n";
        }

        $bot->reply($reply);
    }

    /**
     * @param BotMan $bot
     * @param $match
     * @param $this
     * @param $crawlBank
     */
    private function currencyResponseFb(BotMan $bot, $match, $crawlBank, $nocache = false)
    {
        switch ($match) {
            case 'eur':
            case 'euro':
                $currency = 'eur';
                break;
            default:
                $currency = $match;
        }
        $rates = $this->get_exrate($currency, $bot, $crawlBank, $nocache);
        Log::info($rates);
        $elements = [];
        $currency = strtoupper($currency);
        foreach ($rates as $bank => $bank_rates) {
            $template = ListTemplate::create();

            $template->useCompactView();

            foreach ($bank_rates as $type => $rate) {
                $rates = "\t" . $currency . '  (' . $type . ')  :  ' . $rate . "\n";
                $element = Element::create($rates);
                $element->subtitle($bank . '  rate');
                $template->addElement($element);
                unset($element);
            }

            $bot->reply($template);

        }
    }

    /**
     * @param BotMan $bot
     * @param $match
     * @param $this
     * @param $crawlBank
     */
    private function bankResponseFb(BotMan $bot, $match, $crawlBank, $nocache = false)
    {
        switch ($match) {
            case 'cb':
            case 'cbbank':
                $bank = 'cbbank';
                break;
            default:
                $bank = $match;
        }

        $rates = $this->get_bankrate($bank, $bot, $crawlBank, $nocache);
        Log::info($rates);
        $title = str_replace(' ', '  ', $rates['info']) . ' ';
        $reply = '';
        $exrates = [];

        foreach ($rates['sell_rates'] as $currency => $rate) {
            $exrates[$currency][$this->symbol[$currency] . '  ' . $currency . '  (SELL)'] = $rate;
        }
        foreach ($rates['buy_rates'] as $currency => $rate) {
            $exrates[$currency][$this->symbol[$currency] . '  ' . $currency . '  (BUY)'] = $rate;
        }
        $reply_rates = array_sort_recursive($exrates);

        foreach ($reply_rates as $currency => $rates) {
            $template = ListTemplate::create();

            $template->useCompactView();

            foreach ($rates as $curr => $rate) {
                $reply = $curr . '  :  ' . $rate . "               
                \n";

                $element = Element::create($reply);
                $element->subtitle($bank);
                $template->addElement($element);
                unset($element);
            }

            $bot->reply($template);
        }
    }

    protected function get_exrate($currency, BotMan $bot, CrawlBank $crawlBank, $nocache = false)
    {
        $currency = strtoupper($currency);
        $now = Carbon::now();
        $today = $now->format('Y-m-d a');
        $key = $today . $currency;

        $exrates = $bot->driverStorage();


        if ($exrates->find($key)->has($currency) && !$nocache) {
            Log::info($exrates->find($key));
            Log::info($exrates->get($key));
            $today_rates = $exrates->find($key);
            $rates = $today_rates[$currency];

        } else {

            $central_bank = $crawlBank->getRatesArr('cbm');
            $default_key = array_fill_keys(array_keys($central_bank['rates']), '');
            $banks = ['kbz', 'mcb', 'aya', 'agd', 'cbbank'];
            $bank_rates = [];

            foreach ($banks as $bank) {
                $sell_rates = $crawlBank->getRatesArr($bank, 'sell');
                $buy_rates = $crawlBank->getRatesArr($bank, 'buy');
                $sell = array_merge($default_key, $sell_rates['sell_rates']);
                $buy = array_merge($default_key, $buy_rates['buy_rates']);
                $bank_rates[$currency][$bank]['sell'] = $sell[$currency];
                $bank_rates[$currency][$bank]['buy'] = $buy[$currency];
            }

            $exrates->save($bank_rates, $key);

            $rates = $bank_rates[$currency];
        }

        return $rates;
    }

    protected function get_bankrate($bank, BotMan $bot, CrawlBank $crawlBank, $nocache = false)
    {
        $bank = strtoupper($bank);
        $now = Carbon::now();
        $today = $now->format('Y-m-d-a');
        $key = (string) $today . $bank;

        $exrates = $bot->driverStorage();

        if ($exrates->find($key)->has($key) && !$nocache) {
            $today_rates = $exrates->find($key)->get($key);
        } else {
            $bank_rates[$key] = $crawlBank->getRatesArr($bank);

            $exrates->save($bank_rates, $key);
            $today_rates = $bank_rates[$key];
        }

        Log::info($today_rates);

        return $today_rates;
    }
}