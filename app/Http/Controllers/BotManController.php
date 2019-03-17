<?php

namespace App\Http\Controllers;


use App\Conversations\ExampleConversation;
use App\GoogleTranslate;
use App\Http\Middleware\BotmanMiddleware\ApiAiGoogleTranslate;
use App\Traits\ExBotTrait;
use BotMan\BotMan\BotMan;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\FacebookDriver;
use BotMan\Drivers\Web\WebDriver;
use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    use ExBotTrait;
    private $botman;

    protected $dialogFlowTranslate;

    protected $translate;

    private $banks_url = [];

    private $symbol = [
        'EURO' => '€',
        'EUR' => '€',
        'USD' => '$',
        'THB' => '฿',
        'SGD' => 'S$',
        'MYR' => 'K',
    ];

    public function __construct(GoogleTranslate $translate)
    {
        $this->botman = app('botman-redis');
        $this->dialogFlowTranslate = ApiAiGoogleTranslate::create(env('DIALOGFLOW_CLIENT_TOKEN'));
        $this->translate = $translate;
        $this->dialogFlowTranslate->translate($translate);
        $this->botman->middleware->received($this->dialogFlowTranslate);
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

        $botman->group(['driver' => WebDriver::class], function($botman) use ($crawlBank) {

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

        });

        $botman->group(['driver' => FacebookDriver::class], function($botman) use ($crawlBank) {

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

        });

        $botman->hears('help|^\?$', function (BotMan $bot) {
            $bot->reply('Available commands : 
            usd, eur, euro, thb, sgd,
            agd, aya, cb, kbz, mcb');
        });

        $botman->fallback(function(BotMan $bot)  {
            $message = $bot->getMessage()->getText();
            if (config('app.debug')) {
                Log::info("Message =>");
                Log::info($message);
            }
            $extras = $bot->getMessage()->getExtras();

            if (config('app.debug')) {
                Log::info("Extras =>");
                Log::info($extras);
            }

            $apireply = $extras['apiReply'];

            $lang = $this->translate->getLang($apireply);

            $origin_lang = $this->translate->getLang($message);

            $translated = '';

            if($lang != $origin_lang) {
                $translated = $this->translate->translate($apireply, $origin_lang);
                $apireply = $translated['text'];
            }

            $bot->reply($apireply);

            if (config('app.debug')) {
                Log::info("Translated =>");
                Log::info($translated);
            }
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
