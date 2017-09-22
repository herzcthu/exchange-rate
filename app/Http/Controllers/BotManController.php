<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    private $botman;
    public function __construct()
    {
        $this->botman = app('botman');
    }

    /**
     * Place your BotMan logic here.
     */
    public function handle(CrawlBank $crawlBank)
    {
        $botman = $this->botman;

        $botman->hears('(usd|sgd|thb)', function(BotMan $bot, $currency) use ($crawlBank) {
            $rates = $this->get_exrate($currency, $bot, $crawlBank);
            Log::info($rates);
            $currency = strtoupper($currency);
            foreach($rates as $bank => $bank_rates) {

                $reply = "\n".$currency.' rate for '. $bank;
                foreach($bank_rates as $type => $rate) {
                    $reply .= "\t".$type. ' : ' .$rate."\n";
                }
                $bot->reply($reply);
            }
        });

        $botman->listen();
    }

    public function facebook_handle(CrawlBank $crawlBank)
    {
        $botman = $this->botman;

        $botman->hears('(usd|sgd|thb)', function(BotMan $bot, $currency) use ($crawlBank) {
            $rates = $this->get_exrate($currency, $bot, $crawlBank);
            Log::info($rates);
            $elements = [];
            $currency = strtoupper($currency);
            foreach($rates as $bank => $bank_rates) {
                $element = Element::create($currency.' rate for '. $bank);
                $rates = '';
                foreach($bank_rates as $type => $rate) {
                    $rates .= "\t".$type. ' : ' .$rate."\n";
                }
                $element->subtitle($rates);
                $elements[] = $element;
            }
            $bot->reply(
                GenericTemplate::create()
                    ->addElements($elements)
            );
        });

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }


    protected function get_exrate($currency, BotMan $bot, CrawlBank $crawlBank) {
        $currency = strtoupper($currency);
        $now = Carbon::now();
        $today = $now->format('Y-m-d a');
        $key = $today.$currency;


        $exrates = $bot->driverStorage();



        if($exrates->get($key)) {
            $today_rates = $exrates->get($key);
            return $today_rates[$currency];
        } else {
            $central_bank = $crawlBank->getRatesArr( 'cbm');
            $default_key = array_fill_keys(array_keys($central_bank['rates']), '');
            $banks = ['kbz', 'mcb', 'aya', 'agd', 'cbbank'];
            foreach($banks as $bank) {
                $sell_rates = $crawlBank->getRatesArr($bank, 'sell');
                $buy_rates = $crawlBank->getRatesArr($bank, 'buy');
                $sell = array_merge($default_key, $sell_rates['rates']);
                $buy = array_merge($default_key, $buy_rates['rates']);
                $bank_rates[$key][$currency][$bank]['sell'] = $sell[$currency];
                $bank_rates[$key][$currency][$bank]['buy'] = $buy[$currency];
            }
            $bot->driverStorage()->save($bank_rates);
            return $bank_rates[$key][$currency];
        }
    }
}
