# SYSTEM ARCHITECTURE DOCUMENTATION

**OtakTangkas Platform - Complete Architecture Guide**  
**Version:** 2.0  
**Last Updated:** July 2026  
**Stack:** Laravel 13 + Livewire 4 + Alpine.js + Tailwind CSS 4  
**Real-time:** Laravel Reverb (WebSockets, first-party & self-hosted)

---

## Table of Contents

1. [High-Level Architecture](#high-level-architecture)
2. [Technology Stack](#technology-stack)
3. [Backend Architecture](#backend-architecture)
4. [Frontend Architecture](#frontend-architecture)
5. [Real-Time Architecture](#real-time-architecture)
6. [Caching Strategy](#caching-strategy)
7. [Queue System](#queue-system)
8. [Service Layer Pattern](#service-layer-pattern)
9. [Event & Listener System](#event--listener-system)
10. [Security Architecture](#security-architecture)
11. [Scalability Considerations](#scalability-considerations)
12. [Deployment Architecture](#deployment-architecture)

---

## High-Level Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT LAYER                             │
├─────────────────────────────────────────────────────────────┤
│  Browser (Desktop/Mobile)                                   │
│  ├── Blade Templates (SSR)                                  │
│  ├── Livewire Components (Dynamic)                          │
│  ├── Alpine.js (Client-side interactions)                   │
│  └── Tailwind CSS (Styling)                                 │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    CDN / EDGE LAYER                         │
├─────────────────────────────────────────────────────────────┤
│  CloudFlare / AWS CloudFront                                │
│  ├── Static Assets (CSS, JS, Images)                        │
│  ├── DDoS Protection                                        │
│  └── SSL/TLS Termination                                    │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                 WEB SERVER LAYER                            │
├─────────────────────────────────────────────────────────────┤
│  Nginx / Apache                                             │
│  ├── Request Routing                                        │
│  ├── Load Balancing                                         │
│  └── Static File Serving                                    │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              APPLICATION LAYER (Laravel 13)                 │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────┐  ┌───────────────┐  ┌────────────────┐ │
│  │ HTTP Routes   │  │ Livewire      │  │ Controllers    │ │
│  │ (web.php)     │  │ Components    │  │                │ │
│  └───────────────┘  └───────────────┘  └────────────────┘ │
│                                                             │
│  ┌───────────────┐  ┌───────────────┐  ┌────────────────┐ │
│  │ Services      │  │ Models        │  │ Repositories   │ │
│  │ (Business)    │  │ (Eloquent)    │  │ (Optional)     │ │
│  └───────────────┘  └───────────────┘  └────────────────┘ │
│                                                             │
│  ┌───────────────┐  ┌───────────────┐  ┌────────────────┐ │
│  │ Events        │  │ Jobs/Queues   │  │ Middleware     │ │
│  │ & Listeners   │  │               │  │                │ │
│  └───────────────┘  └───────────────┘  └────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                           │
            ┌──────────────┼──────────────┐
            │              │              │
            ▼              ▼              ▼
┌────────────────┐ ┌──────────────┐ ┌─────────────────┐
│ DATABASE       │ │ CACHE LAYER  │ │ REAL-TIME       │
│ (MySQL)        │ │ (Redis)      │ │ (Reverb)        │
├────────────────┤ ├──────────────┤ ├─────────────────┤
│ - Users        │ │ - Sessions   │ │ - WebSockets    │
│ - Tournaments  │ │ - Query Cache│ │ - Match Events  │
│ - Matches      │ │ - Rate Limit │ │ - Presence Chan.│
│ - Questions    │ │ - Jobs Queue │ │                 │
└────────────────┘ └──────────────┘ └─────────────────┘
```

### Architecture Principles

1. **MVC with Service Layer**: Clean separation of concerns
2. **Component-Based UI**: Livewire for reactive components
3. **Event-Driven**: Decoupled business logic via events
4. **Cache-First**: Reduce database load with Redis
5. **Real-Time Ready**: WebSocket integration for live matches
6. **API-Less Design**: Livewire handles AJAX automatically
7. **Security-First**: Middleware-based authentication/authorization

---

## Technology Stack

### Backend

| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.4 (8.3 min) | Runtime environment |
| Laravel | 13.x | Web application framework |
| Livewire | 4.x | Full-stack reactive framework |
| MySQL | 8.4 LTS | Primary database |
| Redis | 7.0+ | Caching & session storage |
| Laravel Reverb | 1.x | Real-time WebSocket server (first-party, self-hosted) |

### Frontend

| Technology | Version | Purpose |
|------------|---------|---------|
| Blade | Native | Server-side templating |
| Alpine.js | 3.x (bundled with Livewire) | Lightweight JavaScript framework |
| Tailwind CSS | 4.x | Utility-first CSS framework (CSS-first config) |
| Livewire | 4.x | Client-side Livewire handling |

### DevOps & Tools

| Technology | Purpose |
|------------|---------|
| Livewire Starter Kit | Authentication scaffolding (replaces Breeze) |
| Spatie Permission | Role-based access control |
| Filament 5 | Admin panel (forms, tables, resources) |
| Telescope | Debugging & monitoring |
| Pest 4 | Testing framework (incl. browser testing) |
| Laravel Pint | Code style fixer |

---

## Backend Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/          # Traditional controllers
│   │   ├── GameController.php
│   │   ├── TournamentController.php
│   │   └── Auth/             # Starter kit authentication
│   ├── Middleware/           # Request filters
│   │   ├── Authenticate.php
│   │   ├── CheckRole.php
│   │   └── RateLimiter.php
│   └── Requests/             # Form validation
│       └── StoreTournamentRequest.php
├── Livewire/                 # Livewire components
│   ├── Game/
│   │   ├── Board.php         # Tic-tac-toe game board
│   │   └── JoinGame.php      # Match joining
│   ├── Tournament/
│   │   ├── CreateTournament.php
│   │   └── TournamentList.php
│   └── Forms/                # Reusable form components
├── Models/                   # Eloquent models
│   ├── User.php
│   ├── Tournament.php
│   ├── Matches.php
│   ├── Question.php
│   └── Answer.php
├── Services/                 # Business logic layer
│   ├── TournamentService.php
│   ├── MatchService.php
│   └── QuestionService.php
├── Events/                   # Event definitions
│   ├── MatchStarted.php
│   ├── MoveMade.php
│   └── MatchEnded.php
├── Listeners/                # Event handlers
│   ├── NotifyPlayers.php
│   └── UpdateMatchStatistics.php
├── Jobs/                     # Queued tasks
│   ├── SendMatchNotification.php
│   └── GenerateLeaderboard.php
└── Providers/                # Service providers
    ├── AppServiceProvider.php
    └── EventServiceProvider.php
```

### MVC Pattern Implementation

#### Controller Example

```php
// app/Http/Controllers/TournamentController.php
<?php

namespace App\Http\Controllers;

use App\Services\TournamentService;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        private TournamentService $tournamentService
    ) {}

    public function index()
    {
        $tournaments = $this->tournamentService
            ->getUserTournaments(auth()->id());
            
        return view('tournaments.index', compact('tournaments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
        ]);

        $tournament = $this->tournamentService
            ->createTournament($validated, auth()->user());

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('success', 'Tournament created successfully!');
    }
}
```

#### Model Example

```php
// app/Models/Tournament.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    protected $fillable = [
        'user_id', 'name', 'category_id', 'status'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Matches::class);
    }

    // Business logic methods
    public function start(): void
    {
        $this->update(['status' => 'ongoing']);
        event(new \App\Events\TournamentStarted($this));
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }
}
```

---

## Frontend Architecture

### Blade Layout Structure

```
resources/views/
├── layouts/
│   ├── app.blade.php         # Main layout
│   ├── guest.blade.php       # Guest/login layout
│   └── admin.blade.php       # Admin panel layout
├── components/               # Reusable components
│   ├── button.blade.php
│   ├── modal.blade.php
│   └── board-cell.blade.php  # Tic-tac-toe cell
├── livewire/                 # Livewire views
│   ├── game/
│   │   ├── board.blade.php
│   │   └── join-game.blade.php
│   └── tournament/
│       └── create-tournament.blade.php
├── tournaments/              # Tournament pages
│   ├── index.blade.php
│   ├── show.blade.php
│   └── create.blade.php
└── dashboard.blade.php       # User dashboard
```

### Main Layout Example

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OtakTangkas') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <!-- Livewire 4 auto-injects its scripts, and Alpine.js ships bundled
         with Livewire — no CDN tags needed. -->

    <!-- Laravel Echo + the Reverb client are bundled through Vite
         (resources/js/echo.js, loaded by the @vite directive in <head>) -->

    @stack('scripts')
</body>
</html>
```

### Livewire Component Architecture

```php
// app/Livewire/Game/Board.php
<?php

namespace App\Livewire\Game;

use App\Models\Matches;
use App\Services\MatchService;
use Livewire\Component;
use Livewire\Attributes\On;

class Board extends Component
{
    public Matches $match;
    public array $board = [];
    public string $currentTurn = '';
    
    public function mount(Matches $match)
    {
        $this->match = $match;
        $this->loadBoard();
    }

    public function loadBoard()
    {
        $this->board = app(MatchService::class)
            ->getBoardState($this->match);
        $this->currentTurn = $this->match->currentTurnGroup->name ?? '';
    }

    public function makeMove(int $position)
    {
        try {
            app(MatchService::class)->makeMove(
                $this->match,
                auth()->user(),
                $position
            );
            
            $this->loadBoard();
            $this->dispatch('move-made');
            
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    #[On('echo:match.{match.id},MoveMade')]
    public function onMoveMade($data)
    {
        $this->loadBoard();
    }

    public function render()
    {
        return view('livewire.game.board');
    }
}
```

### Livewire View Example

```blade
<!-- resources/views/livewire/game/board.blade.php -->
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- Current Turn Indicator -->
        <div class="text-center mb-4">
            <p class="text-lg font-semibold">
                Current Turn: 
                <span class="text-blue-600">{{ $currentTurn }}</span>
            </p>
        </div>

        <!-- Tic-Tac-Toe Board -->
        <div class="grid grid-cols-3 gap-2">
            @foreach($board as $index => $cell)
                <button 
                    wire:click="makeMove({{ $index }})"
                    @if($cell !== null) disabled @endif
                    class="aspect-square bg-gray-100 rounded-lg 
                           hover:bg-gray-200 transition
                           disabled:opacity-50 disabled:cursor-not-allowed
                           flex items-center justify-center
                           text-4xl font-bold"
                >
                    @if($cell === 'X')
                        <span class="text-blue-600">X</span>
                    @elseif($cell === 'O')
                        <span class="text-red-600">O</span>
                    @endif
                </button>
            @endforeach
        </div>

        <!-- Loading State -->
        <div wire:loading class="text-center mt-4">
            <p class="text-gray-600">Processing move...</p>
        </div>
    </div>
</div>
```

### Alpine.js Integration

```blade
<!-- resources/views/components/modal.blade.php -->
<div 
    x-data="{ open: false }"
    x-show="open"
    @open-modal.window="open = true"
    @close-modal.window="open = false"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500/75"
        @click="open = false"
    ></div>

    <!-- Modal Content -->
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="relative min-h-screen flex items-center justify-center p-4"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            {{ $slot }}
        </div>
    </div>
</div>
```

---

## Real-Time Architecture

### Reverb Configuration

Laravel Reverb is the first-party, self-hosted WebSocket server. It implements the Pusher protocol, so Laravel Echo and the `pusher-js` client work with it unchanged — without the per-message costs of a hosted service.

```php
// config/broadcasting.php (created by `php artisan install:broadcasting`)
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
    ],
],
```

Run the server with `php artisan reverb:start` (managed by Supervisor in production — see [PHASE_7_LAUNCH.md](./PHASE_7_LAUNCH.md)).

### Broadcasting Events

```php
// app/Events/MoveMade.php
<?php

namespace App\Events;

use App\Models\Matches;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MoveMade implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public Matches $match,
        public User $user,
        public int $position,
        public string $symbol
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('match.' . $this->match->id);
    }

    public function broadcastAs(): string
    {
        return 'MoveMade';
    }

    public function broadcastWith(): array
    {
        return [
            'position' => $this->position,
            'symbol' => $this->symbol,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'match_id' => $this->match->id,
        ];
    }
}
```

### Listening to Events (Livewire)

```php
// In Livewire component
use Livewire\Attributes\On;

class Board extends Component
{
    #[On('echo:match.{match.id},MoveMade')]
    public function onMoveMade($event)
    {
        $this->loadBoard();
        $this->dispatch('board-updated');
    }
    
    #[On('echo:match.{match.id},MatchEnded')]
    public function onMatchEnded($event)
    {
        $this->dispatch('show-winner', winner: $event['winner']);
    }
}
```

### Presence Channels (Online Users)

```php
// app/Broadcasting/MatchChannel.php
<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Models\Matches;

class MatchChannel
{
    public function join(User $user, Matches $match): array
    {
        if ($this->userCanJoinMatch($user, $match)) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'profile_photo' => $user->profile_photo_path,
            ];
        }
    }

    private function userCanJoinMatch(User $user, Matches $match): bool
    {
        return $match->tournament->groups()
            ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->exists();
    }
}
```

---

## Caching Strategy

### Cache Layers

```
┌──────────────────────────────────────────────────┐
│              APPLICATION LAYER                   │
└──────────────────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────┐
│          L1 CACHE (Array Cache)                  │
│  - Request-scoped data                           │
│  - Lifetime: Single request                      │
└──────────────────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────┐
│          L2 CACHE (Redis)                        │
│  - Query results                                 │
│  - Session data                                  │
│  - Rate limiting                                 │
│  - Lifetime: Minutes to hours                    │
└──────────────────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────┐
│          PERSISTENT STORAGE (MySQL)              │
│  - Source of truth                               │
└──────────────────────────────────────────────────┘
```

### Cache Implementation

```php
// config/cache.php
'default' => env('CACHE_STORE', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Caching Examples

```php
// Cache categories (rarely change)
$categories = Cache::remember('categories', 3600, function () {
    return Category::all();
});

// Cache user's tournaments
$tournaments = Cache::tags(['user:' . $userId])
    ->remember('tournaments', 1800, function () use ($userId) {
        return Tournament::where('user_id', $userId)->get();
    });

// Invalidate cache
Cache::tags(['user:' . $userId])->flush();

// Cache with lock (prevent cache stampede)
$value = Cache::lock('process-tournament-' . $id, 10)->get(function () {
    return $this->processExpensiveTournamentOperation();
});
```

### Model Caching

```php
// app/Models/Category.php
class Category extends Model
{
    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('categories');
        });

        static::deleted(function () {
            Cache::forget('categories');
        });
    }

    public static function getCached()
    {
        return Cache::remember('categories', 3600, function () {
            return static::all();
        });
    }
}
```

---

## Queue System

### Queue Configuration

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### Job Example

```php
// app/Jobs/SendMatchNotification.php
<?php

namespace App\Jobs;

use App\Models\Matches;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendMatchNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public Matches $match,
        public string $message
    ) {}

    public function handle(): void
    {
        $users = $this->match->tournament->groups()
            ->with('members')
            ->get()
            ->pluck('members')
            ->flatten();

        foreach ($users as $user) {
            Notification::send(
                $user, 
                new \App\Notifications\MatchUpdate($this->match, $this->message)
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Handle job failure
        \Log::error('Failed to send match notification', [
            'match_id' => $this->match->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Dispatching Jobs

```php
// Dispatch immediately
SendMatchNotification::dispatch($match, 'Match started!');

// Dispatch with delay
SendMatchNotification::dispatch($match, 'Match reminder')
    ->delay(now()->addMinutes(5));

// Dispatch on specific queue
SendMatchNotification::dispatch($match, 'Urgent update')
    ->onQueue('high-priority');

// Chain jobs
SendMatchNotification::dispatch($match, 'Starting')
    ->chain([
        new UpdateMatchStatistics($match),
        new GenerateLeaderboard($match->tournament),
    ]);
```

---

## Service Layer Pattern

### Service Structure

```php
// app/Services/MatchService.php
<?php

namespace App\Services;

use App\Models\Matches;
use App\Models\User;
use App\Models\Group;
use App\Events\MoveMade;
use App\Events\MatchEnded;
use Illuminate\Support\Facades\DB;

class MatchService
{
    public function __construct(
        private QuestionService $questionService
    ) {}

    public function createMatch(int $tournamentId, int $group1Id, int $group2Id): Matches
    {
        return DB::transaction(function () use ($tournamentId, $group1Id, $group2Id) {
            $match = Matches::create([
                'tournament_id' => $tournamentId,
                'group1_id' => $group1Id,
                'group2_id' => $group2Id,
                'turn_number' => 0,
                'current_turn_group_id' => $group1Id,
            ]);

            event(new \App\Events\MatchStarted($match));

            return $match;
        });
    }

    public function makeMove(Matches $match, User $user, int $position): void
    {
        DB::transaction(function () use ($match, $user, $position) {
            // Validate move
            $this->validateMove($match, $user, $position);

            // Get user's group
            $group = $this->getUserGroup($match, $user);

            // Determine symbol
            $symbol = $group->id === $match->group1_id ? 'X' : 'O';

            // Record move
            $match->moves()->create([
                'user_id' => $user->id,
                'group_id' => $group->id,
                'symbol' => $symbol,
                'position' => $position,
                'correct' => true, // After question answered
            ]);

            // Update match state
            $match->increment('turn_number');
            $match->update([
                'current_turn_group_id' => $this->getNextTurnGroupId($match),
            ]);

            // Broadcast event
            event(new MoveMade($match, $user, $position, $symbol));

            // Check for winner
            if ($winner = $this->checkWinner($match)) {
                $this->endMatch($match, $winner);
            }
        });
    }

    public function getBoardState(Matches $match): array
    {
        $board = array_fill(0, 9, null);

        foreach ($match->moves as $move) {
            $board[$move->position] = $move->symbol;
        }

        return $board;
    }

    private function validateMove(Matches $match, User $user, int $position): void
    {
        if ($position < 0 || $position > 8) {
            throw new \Exception('Invalid position');
        }

        $board = $this->getBoardState($match);
        if ($board[$position] !== null) {
            throw new \Exception('Position already taken');
        }

        $userGroup = $this->getUserGroup($match, $user);
        if ($userGroup->id !== $match->current_turn_group_id) {
            throw new \Exception('Not your turn');
        }
    }

    private function getUserGroup(Matches $match, User $user): Group
    {
        $group = Group::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();

        if (!$group) {
            throw new \Exception('User not in match');
        }

        return $group;
    }

    private function checkWinner(Matches $match): ?Group
    {
        $board = $this->getBoardState($match);
        $winPatterns = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6],             // Diagonals
        ];

        foreach ($winPatterns as $pattern) {
            [$a, $b, $c] = $pattern;
            if ($board[$a] && $board[$a] === $board[$b] && $board[$a] === $board[$c]) {
                return $board[$a] === 'X' 
                    ? $match->group1 
                    : $match->group2;
            }
        }

        return null;
    }

    private function endMatch(Matches $match, Group $winner): void
    {
        $match->update(['winner_id' => $winner->id]);
        event(new MatchEnded($match, $winner));
    }

    private function getNextTurnGroupId(Matches $match): int
    {
        return $match->current_turn_group_id === $match->group1_id
            ? $match->group2_id
            : $match->group1_id;
    }
}
```

---

## Event & Listener System

### Event Service Provider

```php
// app/Providers/EventServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\MatchStarted::class => [
            \App\Listeners\NotifyMatchPlayers::class,
            \App\Listeners\InitializeMatchBoard::class,
        ],
        
        \App\Events\MoveMade::class => [
            \App\Listeners\BroadcastMove::class,
            \App\Listeners\CheckForWinner::class,
        ],
        
        \App\Events\MatchEnded::class => [
            \App\Listeners\UpdateTournamentStandings::class,
            \App\Listeners\NotifyWinner::class,
            \App\Listeners\GenerateMatchReport::class,
        ],
        
        \App\Events\TournamentStarted::class => [
            \App\Listeners\CreateMatchSchedule::class,
            \App\Listeners\NotifyParticipants::class,
        ],
    ];
}
```

### Listener Example

```php
// app/Listeners/NotifyMatchPlayers.php
<?php

namespace App\Listeners;

use App\Events\MatchStarted;
use App\Jobs\SendMatchNotification;

class NotifyMatchPlayers
{
    public function handle(MatchStarted $event): void
    {
        $match = $event->match;
        
        // Dispatch notification job to queue
        SendMatchNotification::dispatch(
            $match,
            "Your match has started! Join now."
        )->onQueue('notifications');
    }
}
```

---

## Security Architecture

### Authentication Flow

```
┌──────────────┐
│   Browser    │
└──────┬───────┘
       │ 1. Login Request (email/nomor_induk + password)
       ▼
┌──────────────────────┐
│  Livewire Starter Kit│
│  (Auth Component)    │
└──────┬───────────────┘
       │ 2. Validate credentials
       ▼
┌──────────────────────┐
│  Hash verification   │
│  (Bcrypt)            │
└──────┬───────────────┘
       │ 3. Create session
       ▼
┌──────────────────────┐
│  Session Store       │
│  (Redis/Database)    │
└──────┬───────────────┘
       │ 4. Set cookie
       ▼
┌──────────────┐
│   Browser    │
│  (Logged in) │
└──────────────┘
```

### Authorization (Spatie Permission)

```php
// Assign roles
$user->assignRole('student');
$user->assignRole('teacher');

// Check permissions
if ($user->hasRole('teacher')) {
    // Allow tournament creation
}

if ($user->can('create tournaments')) {
    // Allow
}

// Middleware protection
Route::middleware(['role:teacher'])->group(function () {
    Route::post('/tournaments', [TournamentController::class, 'store']);
});
```

### Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
];

// routes/web.php
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    // 60 requests per minute
    Route::post('/tournaments', [TournamentController::class, 'store']);
});

// Custom rate limiter
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
});
```

### CSRF Protection

```blade
<!-- Automatic for POST forms -->
<form method="POST" action="{{ route('tournaments.store') }}">
    @csrf
    <!-- form fields -->
</form>

<!-- Livewire handles automatically -->
<div>
    <button wire:click="createTournament">Create</button>
</div>
```

### XSS Protection

```blade
<!-- Escaped by default -->
<p>{{ $user->name }}</p>

<!-- Raw HTML (only for trusted content) -->
<div>{!! $trustedHtml !!}</div>

<!-- Livewire wire:model prevents XSS -->
<input wire:model="name" type="text">
```

---

## Scalability Considerations

### Horizontal Scaling

```
┌─────────────────────────────────────────────────┐
│            Load Balancer (Nginx)                │
└─────────────────────────────────────────────────┘
                      │
       ┌──────────────┼──────────────┐
       │              │              │
       ▼              ▼              ▼
┌────────────┐  ┌────────────┐  ┌────────────┐
│   App      │  │   App      │  │   App      │
│ Server 1   │  │ Server 2   │  │ Server 3   │
└────────────┘  └────────────┘  └────────────┘
       │              │              │
       └──────────────┼──────────────┘
                      ▼
         ┌────────────────────────┐
         │  Shared Redis Cluster  │
         └────────────────────────┘
                      │
                      ▼
         ┌────────────────────────┐
         │  MySQL (Primary +      │
         │  Read Replicas)        │
         └────────────────────────┘
```

### Database Read Replicas

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            '192.168.1.2',
            '192.168.1.3',
        ],
    ],
    'write' => [
        'host' => ['192.168.1.1'],
    ],
    'driver' => 'mysql',
    // ... other config
],
```

### Queue Workers (Multiple)

```bash
# Supervisor configuration for queue workers
[program:otaktangkas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker.log
```

### CDN Integration

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'), // CloudFront URL
    ],
],
```

---

## Deployment Architecture

### Production Environment

```
┌────────────────────────────────────────────────────┐
│              CloudFlare (CDN + WAF)                │
└────────────────────────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────┐
│         Load Balancer (AWS ALB / DigitalOcean)     │
└────────────────────────────────────────────────────┘
                         │
            ┌────────────┴────────────┐
            │                         │
            ▼                         ▼
┌─────────────────────┐   ┌─────────────────────┐
│  App Server 1       │   │  App Server 2       │
│  (Nginx + PHP-FPM)  │   │  (Nginx + PHP-FPM)  │
└─────────────────────┘   └─────────────────────┘
            │                         │
            └────────────┬────────────┘
                         │
          ┌──────────────┼──────────────┐
          │              │              │
          ▼              ▼              ▼
┌────────────────┐ ┌───────────┐ ┌──────────────┐
│  Redis Cluster │ │  MySQL    │ │  Reverb      │
│  (Cache+Queue) │ │  Database │ │  (WebSocket) │
└────────────────┘ └───────────┘ └──────────────┘
```

### Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate `APP_KEY`
- [ ] Configure database credentials
- [ ] Set Redis cache/queue
- [ ] Configure Reverb credentials & Supervisor process (`reverb:start`)
- [ ] Set up SSL/TLS certificates
- [ ] Configure CORS if needed
- [ ] Set up monitoring (New Relic, Sentry)
- [ ] Configure backups
- [ ] Set up queue workers
- [ ] Configure log rotation
- [ ] Set up cron jobs (`schedule:run`)

---

## Performance Monitoring

### Laravel Telescope

```php
// config/telescope.php
'enabled' => env('TELESCOPE_ENABLED', true),
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => true,
        'slow' => 100, // Log queries > 100ms
    ],
],
```

### Application Performance Monitoring (APM)

```php
// New Relic integration
composer require philkra/laravel-newrelic

// Sentry error tracking
composer require sentry/sentry-laravel
```

---

## Indonesian Market Considerations

1. **Bahasa Indonesia Support**: UTF-8 throughout
2. **Mobile-First**: Most users on smartphones
3. **Low Bandwidth**: Aggressive caching, lazy loading
4. **Offline Mode**: Service Workers for PWA
5. **School Internet**: Handle intermittent connectivity
6. **Payment Integration**: Support local gateways (Midtrans, Xendit)

---

## References

- [Laravel Documentation](https://laravel.com/docs/13.x)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Laravel Reverb Documentation](https://laravel.com/docs/13.x/reverb)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

---

**Document Version:** 2.0  
**Maintained By:** OtakTangkas Development Team  
**Last Review:** 2026-07-03
