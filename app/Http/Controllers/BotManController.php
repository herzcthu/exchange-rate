<?php

namespace App\Http\Controllers;


use App\Conversations\ExampleConversation;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    private $botman;

    private $banks_url = [];

    private $symbol = [
        'EURO' => '€',
        'EUR' => '€',
        'USD' => '$',
        'THB' => '฿',
        'SGD' => 'S$',
    ];

    public function __construct()
    {
        $this->botman = app('botman');
    }

    /**
     * Place your BotMan logic here.
     */
    public function handle(Request $request, CrawlBank $crawlBank)
    {
        $botman = $this->botman;
        if (config('app.debug')) {
            Log::info($request->headers->all());
            Log::info($request->all());
        }

        $botman->hears('(usd|sgd|thb)', function (BotMan $bot, $currency) use ($crawlBank) {
            $rates = $this->get_exrate($currency, $bot, $crawlBank);
            Log::info($rates);
            $currency = strtoupper($currency);
            foreach ($rates as $bank => $bank_rates) {

                $reply = "\n" . $currency . ' rate for ' . $bank;
                foreach ($bank_rates as $type => $rate) {
                    $reply .= "\t" . $type . ' : ' . $rate . "\n";
                }
                $bot->reply($reply);
            }
        });

        $botman->hears('(agd|aya|cbbank|mcb|kbz)', function (BotMan $bot, $bank) use ($crawlBank) {
            $rates = $this->get_bankrate($bank, $bot, $crawlBank);
            Log::info($rates);
            $reply = str_replace(' ', '  ', $rates['info']) . ' ';
            $exrates = [];
            foreach ($rates['sell_rates'] as $currency => $rate) {
                //$exrates[$currency][] = [$currency.' (SELL)' => $rate];
                $exrates[$this->symbol[$currency] . '  ' . $currency . '  (SELL)'] = $rate;
            }
            foreach ($rates['buy_rates'] as $currency => $rate) {
                //$exrates[$currency][] = [$currency.' (BUY)' => $rate];
                $exrates[$this->symbol[$currency] . '  ' . $currency . '  (BUY)'] = $rate;
            }
            $reply_rates = array_sort_recursive($exrates);

            foreach ($reply_rates as $currency => $rate) {
                $reply .= $currency . '  :  ' . $rate . "               
                \n";
            }
            $bot->reply($reply);
        });

        $botman->fallback(function($bot) {
            $bot->reply('Sorry, I did not understand these commands.');
        });

        $botman->listen();
    }

    protected function get_exrate($currency, BotMan $bot, CrawlBank $crawlBank)
    {
        $currency = strtoupper($currency);
        $now = Carbon::now();
        $today = $now->format('Y-m-d a');
        $key = $today . $currency;

        $exrates = $bot->driverStorage();

        if ($exrates->get($key)) {
            $today_rates = $exrates->get($key);
            return $today_rates[$currency];
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
                $bank_rates[$key][$currency][$bank]['sell'] = $sell[$currency];
                $bank_rates[$key][$currency][$bank]['buy'] = $buy[$currency];
            }
            $bot->driverStorage()->save($bank_rates);
            return $bank_rates[$key][$currency];
        }
    }

    protected function get_bankrate($bank, BotMan $bot, CrawlBank $crawlBank)
    {
        $bank = strtoupper($bank);
        $now = Carbon::now();
        $today = $now->format('Y-m-d a');
        $key = $today . $bank;

        $exrates = $bot->driverStorage();

        if ($exrates->get($key)) {
            $today_rates = $exrates->get($key);
            Log::info($today_rates);
            return $today_rates;
        } else {
            $bank_rates[$key] = $crawlBank->getRatesArr($bank);
            Log::info($bank_rates);
        }

        $bot->driverStorage()->save($bank_rates);
        return $bank_rates[$key];
    }

    public function facebook_handle(Request $request, CrawlBank $crawlBank)
    {
        if (config('app.debug')) {
            Log::info($request->headers->all());
            Log::info($request->all());
        }

        $botman = $this->botman;

        //$botman->group(['driver' => FacebookDriver::class], function($bot) use ($crawlBank) {

        $botman->hears('(usd|sgd|thb)', function (BotMan $bot, $currency) use ($crawlBank) {
            $rates = $this->get_exrate($currency, $bot, $crawlBank);
            Log::info($rates);
            $elements = [];
            $currency = strtoupper($currency);
            foreach ($rates as $bank => $bank_rates) {
                $element = Element::create($currency . ' rate for ' . $bank);
                $rates = '';
                foreach ($bank_rates as $type => $rate) {
                    $rates .= "\t" . $type . ' : ' . $rate . "\n";
                }
                $element->subtitle($rates);
                $elements[] = $element;
            }
            $bot->reply(
                GenericTemplate::create()
                    ->addElements($elements)
            );
        });

        $botman->hears('(agd|aya|cbbank|mcb|kbz)', function (BotMan $bot, $bank) use ($crawlBank) {
            $rates = $this->get_bankrate($bank, $bot, $crawlBank);
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

        });
        //});

        $botman->fallback(function($bot) {
            $bot->reply('Sorry, I did not understand these commands.');
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
}
