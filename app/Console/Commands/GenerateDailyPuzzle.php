<?php

namespace App\Console\Commands;

use App\Services\PuzzleGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDailyPuzzle extends Command
{
    protected $signature = 'puzzle:generate
        {date? : Target date (any Carbon-parsable string); defaults to today}
        {--days=1 : Generate puzzles for this many consecutive days}';

    protected $description = 'Generate the shared daily puzzle for a date (idempotent)';

    public function handle(PuzzleGeneratorService $generator): int
    {
        $start = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : today();

        $days = max(1, (int) $this->option('days'));

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $puzzle = $generator->generateForDate($date);

            $this->info(sprintf(
                '%s: %s puzzle #%d (%d soal, %d XP / %d koin, %ds)',
                $date->toDateString(),
                $puzzle->difficulty->value,
                $puzzle->id,
                count($puzzle->question_ids),
                $puzzle->xp_reward,
                $puzzle->coins_reward,
                $puzzle->time_limit,
            ));
        }

        return self::SUCCESS;
    }
}
