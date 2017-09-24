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
        'usd',
        'sgd',
        'eur',
        'thb'
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


//        foreach($currencies as $currency) {
//            $this->get_exrate($currency, $this->botman,$crawlBank,true);
//        }

        foreach ($banks as $bank) {
            $storage = $this->botMan->driverStorage();
            $key = (string) $today . $bank;
            $bank_rates[$key] = $crawlBank->getRatesArr($bank);
            $storage->save($bank_rates);
            unset($storage);
        }

    }
}
