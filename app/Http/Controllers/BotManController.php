<?php

namespace App\Http\Controllers;


use App\Conversations\ExampleConversation;
use App\Traits\ExBotTrait;
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
    use ExBotTrait;
    private $botman;

    private $banks_url = [];

    private $symbol = [
        'EURO' => '€',
        'EUR' => '€',
        'USD' => '$',
        'THB' => '฿',
        'SGD' => 'S$',
        'MYR' => 'K',
    ];

    public function __construct()
    {
        $this->botman = app('botman-redis');
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

        $botman->hears('^(usd|sgd|thb|eur|euro)$', function (BotMan $bot, $match) use ($crawlBank) {

            $this->CurrencyResponseWeb($bot, $match, $crawlBank, false);

        });

        $botman->hears('latest (usd|sgd|thb|eur|euro)', function (BotMan $bot, $match) use ($crawlBank) {

            $this->CurrencyResponseWeb($bot, $match, $crawlBank, true);

        });

        $botman->hears('^(agd|aya|cb|cbbank|mcb|kbz)$', function (BotMan $bot, $match) use ($crawlBank) {

            $this->bankResponseWeb($bot, $match, $crawlBank);

        });

        $botman->hears('latest (agd|aya|cbbank|mcb|kbz)', function (BotMan $bot, $match) use ($crawlBank) {

            $this->bankResponseWeb($bot, $match, $crawlBank, true);
        });

        $botman->fallback(function($bot) {
            $bot->reply('Sorry, I did not understand these commands.');
        });

        $botman->listen();
    }


    public function facebook_handle(Request $request, CrawlBank $crawlBank)
    {
        if (config('app.debug')) {
            Log::info($request->headers->all());
            Log::info($request->all());
        }

        $botman = $this->botman;

        //$botman->group(['driver' => FacebookDriver::class], function($bot) use ($crawlBank) {

        $botman->hears('^(usd|sgd|thb|eur|euro)$', function (BotMan $bot, $match) use ($crawlBank) {

            $this->currencyResponseFb($bot, $match, $crawlBank);

        });

        $botman->hears('^(agd|aya|cb|cbbank|mcb|kbz)$', function (BotMan $bot, $match) use ($crawlBank) {

            $this->bankResponseFb($bot, $match, $crawlBank);

        });

        $botman->hears('latest (usd|sgd|thb|eur|euro)', function (BotMan $bot, $match) use ($crawlBank) {

            $this->currencyResponseFb($bot, $match, $crawlBank);

        });

        $botman->hears('latest (agd|aya|cb|cbbank|mcb|kbz)', function (BotMan $bot, $match) use ($crawlBank) {

            $this->bankResponseFb($bot, $match, $crawlBank);

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
