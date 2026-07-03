<?php

namespace App\Enums;

enum MatchMode: string
{
    case QuickPlay = 'quick_play';
    case Invite = 'invite';
    case Practice = 'practice';
    case DailyPuzzle = 'daily_puzzle';
}
