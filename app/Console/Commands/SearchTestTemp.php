<?php

namespace App\Console\Commands;

use App\Carer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SearchTestTemp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arc:search:test {name} {--fuzzy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = DB::table('carers')->count();
        $now = microtime(true);

        $fuzzy = $this->option('fuzzy');

        $query = strtolower($this->argument("name"));

        if ($fuzzy) {
            $soundex = soundex($query);
            $soundexPrefix = substr($soundex, 0, 3);
            $carers = DB::select("SELECT * FROM carers WHERE SOUNDEX(name) LIKE ?", [$soundexPrefix . "%"]);
            // $carers = Carer::query()->where('name', 'SOUNDS LIKE', "{$query}")->get();
        } else {
             $carers = Carer::query()->where('name', 'LIKE', "%$query%")->get();
        }

        $startsWithExact = [];
        $wholeWord = [];
        $theRest = [];

        foreach ($carers as $carer) {
            $names = array_map('strtolower', explode(" ", $carer->name));

            if (count($names) == 0) {
                // WTF?
                continue;
            } elseif (strtolower($names[0]) === strtolower($query)) {
                $startsWithExact[] = $carer;
            } elseif (in_array($query, $names)) {
                $wholeWord[] = $carer;
            } else {
                $theRest[] = $carer;
            }
        }

        $results = array_merge($startsWithExact, $wholeWord, $theRest);
        $cnt = 0;
        foreach ($results as $item) {
            if ($cnt++ > 10) {
                break;
            }
            $this->line($item->name);
        }
        $this->info("Search took " . str(round(microtime(true) - $now, 4)) . " seconds to search ".$count." carers.");

        return Command::SUCCESS;
    }
}
