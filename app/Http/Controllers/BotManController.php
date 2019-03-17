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

            $this->processMsg($crawlBank, 'web');

        });

        $botman->group(['driver' => FacebookDriver::class], function($botman) use ($crawlBank) {

            $this->processMsg($crawlBank, 'facebook');

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

            $origin_lang = $this->translate->getLang($message);

            $translated = '';

            $fallback_msg = 'Sorry I don\'t understand what you are saying. Something might wrong. Please contact Administrator.';

            if($origin_lang != 'en') {
                $translated = $this->translate->translate($fallback_msg, $origin_lang);
                $fallback_msg = $translated['text'];
            }

            $bot->reply($fallback_msg);

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


    private function processMsg(CrawlBank $crawlBank, $channel)
    {
        $botman = $this->botman;
        $botman->hears('(.*)',function(BotMan $bot) use ($crawlBank, $channel) {
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

            $apiParameters = $extras['apiParameters'];

            if(array_key_exists('currency-name', $apiParameters)) {
                $currency = $apiParameters['currency-name'];
                if(!empty($currency)) {
                    $this->CurrencyResponse($bot, $currency, $crawlBank, false, $channel);
                }
            }

            if(array_key_exists('bank-name', $apiParameters)) {
                $bank = $apiParameters['bank-name'];
                if(!empty($bank)) {
                    $this->bankResponse($bot, $bank, $crawlBank, false, $channel);
                }
            }

            if(!empty($apireply)) {
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
            }

        })->middleware($this->dialogFlowTranslate);
    }

}
