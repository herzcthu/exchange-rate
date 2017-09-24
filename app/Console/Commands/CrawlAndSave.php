<?php

namespace App\Console\Commands;

use App\Traits\ExBotTrait;
use BotMan\BotMan\BotMan;
use Carbon\Carbon;
use Herzcthu\ExchangeRates\CrawlBank;
use Illuminate\Console\Command;

class CrawlAndSave extends Command
{
    use ExBotTrait;

    private $storage;

    private $botMan;

    private $currencies = [
        'USD',
        'SGD',
        'EUR',
        'THB'
    ];

    private $banks = [
        'AGD',
        'AYA',
        'CBBANK',
        'KBZ',
        'MCB'
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl and save exchange rates and bank rates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->botMan = app('botman-redis');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(CrawlBank $crawlBank)
    {
        $currencies = $this->currencies;
        $banks = $this->banks;
        $now = Carbon::now();
        $today = $now->format('Y-m-d-a');

        $storage = $this->botMan->driverStorage();

        foreach($currencies as $currency) {
            $currency = strtoupper($currency);
            $central_bank = $crawlBank->getRatesArr('cbm');
            $default_key = array_fill_keys(array_keys($central_bank['rates']), '');
            $bank_rates = [];
            $key = (string) $today . $currency;

            foreach ($banks as $bank) {
                $sell_rates = $crawlBank->getRatesArr($bank, 'sell');
                $buy_rates = $crawlBank->getRatesArr($bank, 'buy');
                $sell = array_merge($default_key, $sell_rates['sell_rates']);
                $buy = array_merge($default_key, $buy_rates['buy_rates']);
                $bank_rates[$currency][$bank]['sell'] = $sell[$currency];
                $bank_rates[$currency][$bank]['buy'] = $buy[$currency];
            }
            $storage->save($bank_rates, $key);
            unset($bank_rates);
        }

        foreach ($banks as $bank) {

            $key = (string) $today . $bank;
            $bank_rates[$key] = $crawlBank->getRatesArr($bank);
            $storage->save($bank_rates, $key);
            unset($bank_rates);
        }

    }
}
