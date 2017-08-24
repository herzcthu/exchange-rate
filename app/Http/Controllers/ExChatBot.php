<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mpociot\BotMan\BotMan;

class ExChatBot extends Controller
{
    public function autoreply(Request $request, CrawlBank $crawlBank)
    {
        Log::info(json_encode($request->headers->all()));
        Log::info(json_encode($request->all()));

        $botman = app('botman');
        $botman->verifyServices(env('CHATBOT_TOKEN'));
        // Simple respond method
//        $botman->hears('Hello', function (BotMan $bot) {
//            $user = $bot->userStorage()->get();
//            if($user->has('firstname')) {
//                $bot->reply('Hi there '.$user->getFirstName());
//            } else {
//                $bot->userStorage()->save([
//                    'firstname' => $user->getFirstName()
//                ]);
//            }
//
//        });

        $botman->hears('(usd|USD|sgd|SGD|thb|THB)', function(BotMan $bot, $currency) use ($crawlBank) {
            $rates = $this->get_exrate($currency, $bot, $crawlBank);
            $bot->reply($rates);
        });

        $botman->hears('Who am I', function(BotMan $bot) {
            $user = $bot->getUser();
            $bot->reply('Hello '.$user->getFirstName().' '.$user->getLastName());
            $bot->reply('Your username is: '.$user->getUsername());
            $bot->reply('Your ID is: '.$user->getId());
        });

        $botman->hears("{message}([?\.၊။])", function (BotMan $bot, $message) {
            // Store information for the currently logged in user.
            // You can also pass a user-id / key as a second parameter.
            $bot->userStorage()->save([
                'message' => $message
            ]);

            $bot->reply($message);
        });

        $botman->fallback(function($bot) {
            $bot->reply('Sorry, I did not understand these commands.');
        });

        $botman->listen();

    }

    protected function get_exrate($currency, BotMan $bot, CrawlBank $crawlBank) {
            $now = Carbon::now();
            $today = $now->format('Y-m-d a');
            $central_bank = $crawlBank->rates( 'cbm');
            $cbm_arr = json_decode($central_bank, true);
            $default_key = array_flip(array_keys($cbm_arr['rates']));
            $banks = ['kbz', 'mcb', 'aya', 'agd', 'cbbank'];
            foreach($banks as $bank) {
                $sell_rates = json_decode($crawlBank->rates($bank, 'sell'), true);
                $buy_rates = json_decode($crawlBank->rates($bank, 'buy'), true);
                $sell = array_merge($default_key, $sell_rates['rates']);
                $buy = array_merge($default_key, $buy_rates['rates']);
                $bank_rates[$today][$currency][$bank]['sell'] = $sell[$currency];
                $bank_rates[$today][$currency][$bank]['buy'] = $buy[$currency];
            }

            $exrates = $bot->driverStorage()->get();

            if($exrates->has($today)) {
                $today_rates = $exrates->get($today);
                return $today_rates[$currency];
            } else {
                $bot->driverStorage()->save($bank_rates);
                return $bank_rates[$today][$currency];
            }
    }

}
