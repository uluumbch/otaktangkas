# API ENDPOINTS DOCUMENTATION

**OtakTangkas Platform - Complete API Reference**  
**Version:** 2.0  
**Last Updated:** July 2026  
**Architecture:** Livewire-First (Minimal Traditional REST)

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Livewire Component Endpoints](#livewire-component-endpoints)
4. [Traditional HTTP Routes](#traditional-http-routes)
5. [Real-Time Event Channels](#real-time-event-channels)
6. [Rate Limiting](#rate-limiting)
7. [Error Handling](#error-handling)
8. [Request/Response Examples](#requestresponse-examples)
9. [Webhook Endpoints](#webhook-endpoints)
10. [Admin API (Filament)](#admin-api-filament)

---

## Overview

OtakTangkas uses a **Livewire-first architecture**, meaning most API interactions happen through Livewire components rather than traditional REST endpoints. This provides:

- **Automatic CSRF protection**
- **Real-time reactivity** without manual AJAX
- **Server-side validation** built-in
- **Reduced boilerplate** code

### Architecture Summary

```
┌─────────────────────────────────────────────────┐
│              CLIENT (Browser)                   │
├─────────────────────────────────────────────────┤
│  • Livewire Wire (AJAX handling)                │
│  • Alpine.js (Client interactions)              │
│  • Laravel Echo → Reverb (Real-time events)     │
└─────────────────────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        ▼            ▼            ▼
┌──────────────┐ ┌───────────┐ ┌──────────────┐
│  Livewire    │ │  HTTP     │ │  WebSocket   │
│  Components  │ │  Routes   │ │  Channels    │
└──────────────┘ └───────────┘ └──────────────┘
        │            │            │
        └────────────┼────────────┘
                     ▼
         ┌──────────────────────┐
         │  Laravel Backend     │
         │  (Controllers,       │
         │   Services, Models)  │
         └──────────────────────┘
```

---

## Authentication

### Authentication Endpoints (Livewire Starter Kit)

All authentication handled by the official Laravel Livewire starter kit (login, registration, password reset, email verification). No custom API tokens needed for web app.

#### Login

**Endpoint:** `POST /login`  
**Purpose:** Authenticate user and create session  
**Form Data:**

```json
{
  "email": "user@example.com",
  "password": "password123",
  "remember": true
}
```

**Alternative (Indonesian Students):**

```json
{
  "nomor_induk": "123456789",
  "password": "password123",
  "remember": true
}
```

**Response:** Redirects to `/dashboard` on success

**Error Response (422):**

```json
{
  "message": "The provided credentials do not match our records.",
  "errors": {
    "email": ["These credentials do not match our records."]
  }
}
```

---

#### Register

**Endpoint:** `POST /register`  
**Purpose:** Create new user account  
**Form Data:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "nomor_induk": "123456789",
  "school_id": 1,
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:** Redirects to `/dashboard` with session

---

#### Logout

**Endpoint:** `POST /logout`  
**Purpose:** Destroy user session  
**Headers:** `X-CSRF-TOKEN` (automatic via Blade)  
**Response:** Redirects to `/`

---

#### Password Reset

**Endpoint:** `POST /forgot-password`  
**Form Data:**

```json
{
  "email": "user@example.com"
}
```

**Response (200):**

```json
{
  "message": "We have emailed your password reset link!"
}
```

---

### Authentication Middleware

**Applied to protected routes:**

```php
Route::middleware('auth')->group(function () {
    // Protected routes
});
```

**Check authentication in Livewire:**

```php
public function mount()
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }
}
```

---

## Livewire Component Endpoints

Livewire components communicate via **automatic AJAX endpoints**. No manual API calls needed.

### Component URL Pattern

```
POST /livewire/update
POST /livewire/message/{component}
```

**Headers (Automatic):**

```
Content-Type: application/json
X-Livewire: true
X-CSRF-TOKEN: {token}
```

---

### Game Components

#### 1. Board Component

**Component:** `App\Livewire\Game\Board`  
**Route:** Embedded in `/tournaments/{tournament}/matches/{match}`

**Available Actions:**

##### makeMove

**Description:** Player makes a move on tic-tac-toe board  
**Parameters:**

- `position` (int): Board position (0-8)

**Livewire Call:**

```javascript
// Automatic via wire:click
<button wire:click="makeMove({{ $position }})">
```

**Backend Method:**

```php
public function makeMove(int $position): void
{
    // Validates turn, position availability
    // Records move to database
    // Broadcasts event to other players
    // Updates board state
}
```

**Events Dispatched:**

- `move-made`: Confirms move to UI
- `error`: If move invalid

**Real-time Broadcast:** `MoveMade` event to `match.{id}` channel

---

##### loadBoard

**Description:** Refresh board state  
**Livewire Call:**

```php
$this->loadBoard();
```

**Response:** Updates `$board` property with current state

---

##### useHelp

**Description:** Use hint/help during match  
**Livewire Call:**

```javascript
wire:click="useHelp"
```

**Backend:** Records help usage, provides hint

---

#### 2. JoinGame Component

**Component:** `App\Livewire\Game\JoinGame`

##### joinMatch

**Description:** User joins/spectates a match  
**Livewire Call:**

```javascript
wire:click="joinMatch"
```

**Backend:**

```php
public function joinMatch(): void
{
    UserJoin::create([
        'user_id' => auth()->id(),
        'matches_id' => $this->matchId,
    ]);
    
    $this->dispatch('joined-match');
}
```

---

### Tournament Components

#### 3. CreateTournament Component

**Component:** `App\Livewire\Tournament\CreateTournament`

**Form Properties:**

```php
public string $name = '';
public int $category_id;
public array $groups = [];
```

##### saveTournament

**Description:** Create new tournament  
**Livewire Call:**

```javascript
wire:click="saveTournament"
```

**Validation:**

```php
$this->validate([
    'name' => 'required|string|max:255',
    'category_id' => 'required|exists:categories,id',
    'groups' => 'required|array|min:2',
    'groups.*.name' => 'required|string',
    'groups.*.members' => 'required|array|min:2|max:4',
]);
```

**Response:** Redirects to tournament view

---

##### addGroup

**Description:** Add new group to tournament  
**Livewire Call:**

```javascript
wire:click="addGroup"
```

**Backend:** Adds empty group to `$groups` array

---

##### removeGroup

**Description:** Remove group from tournament  
**Parameters:**

- `index` (int): Group index

**Livewire Call:**

```javascript
wire:click="removeGroup({{ $index }})"
```

---

#### 4. TournamentList Component

**Component:** `App\Livewire\Tournament\TournamentList`

**Properties:**

```php
public string $search = '';
public string $status = 'all'; // all, ongoing, completed
public int $perPage = 10;
```

##### loadTournaments

**Description:** Load filtered tournaments  
**Automatic:** Runs on mount and when properties change

**Query:**

```php
$query = Tournament::query()
    ->with('user', 'category')
    ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
    ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
    ->latest();

return $query->paginate($this->perPage);
```

---

### Question Management Components

#### 5. KelolaSoalAdd Component

**Component:** `App\Livewire\KelolaSoalAdd`

**Form Properties:**

```php
public string $text = '';
public int $category_id;
public array $answers = [];
public int $correct_answer_index;
```

##### save

**Description:** Create new question with answers  
**Validation:**

```php
$this->validate([
    'text' => 'required|string|min:10',
    'category_id' => 'required|exists:categories,id',
    'answers' => 'required|array|min:2|max:5',
    'answers.*' => 'required|string',
    'correct_answer_index' => 'required|integer|min:0',
]);
```

**Backend:**

```php
public function save(): void
{
    DB::transaction(function () {
        $question = Question::create([
            'text' => $this->text,
            'category_id' => $this->category_id,
        ]);

        foreach ($this->answers as $index => $answerText) {
            Answer::create([
                'question_id' => $question->id,
                'answer_text' => $answerText,
                'is_correct' => $index === $this->correct_answer_index,
            ]);
        }
    });

    session()->flash('success', 'Soal berhasil ditambahkan!');
    return redirect()->route('kelola-soal.index');
}
```

---

#### 6. KelolaSoalEdit Component

**Component:** `App\Livewire\KelolaSoalEdit`

##### update

**Description:** Update existing question  
**Similar to save, but uses `Question::find($id)->update()`

---

### User Management Components

#### 7. KelolaPenggunaTable Component

**Component:** `App\Livewire\KelolaPenggunaTable`

**Uses:** PowerGrid for data tables

**Features:**

- Pagination
- Sorting
- Filtering
- Bulk actions

**Automatic endpoints via PowerGrid**

---

### Answer Question Components

#### 8. JawabSoal Component

**Component:** `App\Livewire\JawabSoal`

##### submitAnswer

**Description:** Submit answer to question during match  
**Parameters:**

- `answerId` (int): Selected answer ID

**Livewire Call:**

```javascript
wire:click="submitAnswer({{ $answerId }})"
```

**Backend:**

```php
public function submitAnswer(int $answerId): void
{
    $answer = Answer::findOrFail($answerId);
    
    if ($answer->is_correct) {
        $this->dispatch('answer-correct', position: $this->position);
        $this->allowMove = true;
    } else {
        $this->dispatch('answer-incorrect');
        $this->wrongAnswers++;
        
        if ($this->wrongAnswers >= 3) {
            $this->dispatch('turn-skipped');
            // Switch turn to other team
        }
    }
}
```

**Events:**

- `answer-correct`: Unlocks board move
- `answer-incorrect`: Shows error
- `turn-skipped`: Switches turn after 3 wrong answers

---

## Traditional HTTP Routes

### Web Routes (Blade Pages)

#### Dashboard

**Endpoint:** `GET /dashboard`  
**Middleware:** `auth`  
**Controller:** `DashboardController@index`  
**Response:** Blade view with user stats

---

#### Tournament Routes

##### List Tournaments

**Endpoint:** `GET /tournaments`  
**Middleware:** `auth`  
**Controller:** `TournamentController@index`  
**Query Parameters:**

- `status` (string): Filter by status
- `search` (string): Search by name
- `page` (int): Pagination

**Response:** Blade view with tournaments

---

##### Show Tournament

**Endpoint:** `GET /tournaments/{tournament}`  
**Middleware:** `auth`  
**Controller:** `TournamentController@show`  
**Response:** Tournament details with groups and matches

---

##### Create Tournament Page

**Endpoint:** `GET /tournaments/create`  
**Middleware:** `auth`, `role:teacher`  
**Controller:** `TournamentController@create`  
**Response:** Tournament creation form

---

##### Store Tournament

**Endpoint:** `POST /tournaments`  
**Middleware:** `auth`, `role:teacher`  
**Controller:** `TournamentController@store`  
**Form Data:**

```json
{
  "name": "Turnamen Matematika",
  "category_id": 1,
  "groups": [
    {
      "name": "Tim Alpha",
      "members": [10, 11, 12]
    },
    {
      "name": "Tim Beta",
      "members": [13, 14, 15]
    }
  ]
}
```

**Response:** Redirect to tournament view

---

##### Start Tournament

**Endpoint:** `POST /tournaments/{tournament}/start`  
**Middleware:** `auth`, `role:teacher`  
**Controller:** `TournamentController@start`  
**Response:** Redirect with success message

---

#### Match Routes

##### Show Match

**Endpoint:** `GET /tournaments/{tournament}/matches/{match}`  
**Middleware:** `auth`  
**Controller:** N/A (Direct Blade view)  
**Response:** Match board view (with Livewire Board component)

---

#### Question Routes

##### List Questions

**Endpoint:** `GET /kelola-soal`  
**Middleware:** `auth`, `role:teacher|admin`  
**Response:** Question management page

---

##### Import Questions

**Endpoint:** `POST /kelola-soal/import`  
**Middleware:** `auth`, `role:teacher|admin`  
**Form Data:** `file` (Excel/CSV)  
**Response:** Success/error message

---

##### Export Questions

**Endpoint:** `GET /kelola-soal/export`  
**Middleware:** `auth`, `role:teacher|admin`  
**Response:** Excel download

---

#### User Management Routes

##### List Users

**Endpoint:** `GET /kelola-pengguna`  
**Middleware:** `auth`, `role:admin`  
**Response:** User management page

---

##### Show User

**Endpoint:** `GET /kelola-pengguna/{user}`  
**Middleware:** `auth`, `role:admin`  
**Response:** User details with match history

---

##### Update User

**Endpoint:** `PUT /kelola-pengguna/{user}`  
**Middleware:** `auth`, `role:admin`  
**Form Data:**

```json
{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "school_id": 2,
  "roles": ["student"]
}
```

---

#### Category Routes

##### List Categories

**Endpoint:** `GET /categories`  
**Middleware:** `auth`  
**Response:** JSON array of categories

```json
[
  {"id": 1, "name": "Matematika"},
  {"id": 2, "name": "Fisika"},
  {"id": 3, "name": "Bahasa Indonesia"}
]
```

---

##### Store Category

**Endpoint:** `POST /categories`  
**Middleware:** `auth`, `role:teacher|admin`  
**Form Data:**

```json
{
  "name": "Kimia"
}
```

**Response (201):**

```json
{
  "id": 6,
  "name": "Kimia",
  "user_id": 2,
  "created_at": "2025-02-15T10:30:00.000000Z"
}
```

---

#### School Routes

##### List Schools

**Endpoint:** `GET /api/schools`  
**Middleware:** `auth`  
**Response:**

```json
[
  {
    "id": 1,
    "name": "SMA Negeri 1 Jakarta",
    "address": "Jl. Merdeka No. 123",
    "phone": "021-12345678"
  }
]
```

---

### Static Content Routes

#### How to Play

**Endpoint:** `GET /cara-bermain`  
**Response:** Tutorial page

---

#### About

**Endpoint:** `GET /tentang`  
**Response:** About page

---

## Real-Time Event Channels

### Public Channels

#### Tournament Channel

**Channel:** `tournament.{tournamentId}`  
**Events:**

- `TournamentStarted`: Broadcast when tournament begins
- `TournamentEnded`: Broadcast when tournament completes
- `MatchScheduled`: New match created

**Subscription (Livewire):**

```php
protected function getListeners()
{
    return [
        "echo:tournament.{$this->tournamentId},TournamentStarted" => 'onTournamentStarted',
        "echo:tournament.{$this->tournamentId},MatchScheduled" => 'onMatchScheduled',
    ];
}
```

**JavaScript Subscription:**

```javascript
window.Echo.channel('tournament.' + tournamentId)
    .listen('TournamentStarted', (e) => {
        console.log('Tournament started:', e);
    });
```

---

#### Match Channel

**Channel:** `match.{matchId}`  
**Events:**

- `MoveMade`: Player made a move
- `MatchEnded`: Match completed
- `QuestionPresented`: New question shown
- `TurnChanged`: Turn switched to other team

**Event Payload (MoveMade):**

```json
{
  "position": 4,
  "symbol": "X",
  "user": {
    "id": 10,
    "name": "John Doe"
  },
  "match_id": 123,
  "timestamp": "2025-02-15T14:30:22.000000Z"
}
```

**Livewire Listener:**

```php
#[On('echo:match.{match.id},MoveMade')]
public function onMoveMade($event)
{
    $this->loadBoard();
    $this->dispatch('board-updated');
}
```

---

### Presence Channels (Authentication Required)

#### Match Presence

**Channel:** `presence-match.{matchId}`  
**Purpose:** Track who's online in match  
**Authorization:**

```php
Broadcast::channel('presence-match.{matchId}', function ($user, $matchId) {
    $match = Matches::find($matchId);
    return $match && $match->tournament->groups()
        ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
        ->exists()
        ? ['id' => $user->id, 'name' => $user->name]
        : null;
});
```

**Events** (Pusher-protocol event names — Reverb implements the same protocol):

- `pusher:member_added`: User joins match
- `pusher:member_removed`: User leaves match

**JavaScript:**

```javascript
let presenceChannel = window.Echo.join('presence-match.' + matchId)
    .here((users) => {
        console.log('Currently online:', users);
    })
    .joining((user) => {
        console.log(user.name + ' joined');
    })
    .leaving((user) => {
        console.log(user.name + ' left');
    });
```

---

### Private Channels

#### User Notification Channel

**Channel:** `private-App.Models.User.{userId}`  
**Purpose:** Personal notifications  
**Events:**

- `MatchInvitation`: Invited to match
- `TournamentReminder`: Tournament starting soon

**Authorization:** Automatic via Laravel's broadcasting

**Livewire:**

```php
protected function getListeners()
{
    return [
        "echo-private:App.Models.User.{$this->userId},MatchInvitation" => 'onMatchInvitation',
    ];
}
```

---

## Rate Limiting

### Global Rate Limits

**Applied to all routes:**

```php
// 1000 requests per minute per IP
RateLimiter::for('global', function (Request $request) {
    return Limit::perMinute(1000)->by($request->ip());
});
```

### API-Specific Limits

#### Authentication Routes

```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

**Exceeded Response (429):**

```json
{
  "message": "Too many login attempts. Please try again in 60 seconds."
}
```

---

#### Tournament Creation

```php
Route::post('/tournaments', [TournamentController::class, 'store'])
    ->middleware('throttle:10,1'); // 10 per minute
```

---

#### Match Actions

**Livewire throttling:**

```php
// In component
public function makeMove($position)
{
    $this->middleware(['throttle:30,1']); // 30 moves per minute
    
    // ... move logic
}
```

---

### Custom Rate Limiter (Per User)

```php
RateLimiter::for('per-user', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(100)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});
```

---

## Error Handling

### Standard Error Responses

#### 400 Bad Request

```json
{
  "message": "Invalid input data",
  "errors": {
    "name": ["The name field is required."],
    "category_id": ["The selected category is invalid."]
  }
}
```

---

#### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

**Automatic redirect to login page in web routes**

---

#### 403 Forbidden

```json
{
  "message": "This action is unauthorized."
}
```

---

#### 404 Not Found

```json
{
  "message": "Tournament not found."
}
```

---

#### 422 Unprocessable Entity (Validation)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

---

#### 429 Too Many Requests

```json
{
  "message": "Too many requests. Please try again in 60 seconds.",
  "retry_after": 60
}
```

---

#### 500 Internal Server Error

```json
{
  "message": "Server error. Please try again later.",
  "error_id": "ERR-20250215-143022-ABC123"
}
```

**Production:** Generic message  
**Development:** Full stack trace via Laravel Telescope

---

### Livewire Error Handling

**Component Method:**

```php
public function makeMove($position)
{
    try {
        // ... logic
    } catch (ValidationException $e) {
        $this->dispatch('error', message: $e->getMessage());
    } catch (\Exception $e) {
        Log::error('Match move error', [
            'match_id' => $this->match->id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);
        
        $this->dispatch('error', message: 'An error occurred. Please try again.');
    }
}
```

**Frontend Listener:**

```javascript
Livewire.on('error', (data) => {
    alert(data.message);
});
```

---

## Request/Response Examples

### Create Tournament (Complete Flow)

#### 1. Load Create Page

**Request:**

```
GET /tournaments/create
Cookie: laravel_session=...
```

**Response:** HTML page with Livewire component

---

#### 2. Fill Form (Livewire Auto-Sync)

**Automatic AJAX (per field):**

```
POST /livewire/update
Content-Type: application/json
X-CSRF-TOKEN: abc123...

{
  "fingerprint": {...},
  "serverMemo": {...},
  "updates": [
    {
      "type": "syncInput",
      "payload": {
        "name": "name",
        "value": "Turnamen Matematika SMA"
      }
    }
  ]
}
```

---

#### 3. Submit Form

**Livewire Call:**

```
POST /livewire/message/tournament.create-tournament
Content-Type: application/json

{
  "fingerprint": {...},
  "serverMemo": {...},
  "updates": [
    {
      "type": "callMethod",
      "payload": {
        "method": "saveTournament",
        "params": []
      }
    }
  ]
}
```

**Response (200):**

```json
{
  "effects": {
    "redirectTo": "/tournaments/123",
    "flash": {
      "success": "Tournament created successfully!"
    }
  },
  "serverMemo": {...}
}
```

---

### Make Move in Match

#### 1. Click Board Cell

**Frontend:**

```javascript
wire:click="makeMove(4)" // Middle cell
```

**Livewire Request:**

```
POST /livewire/message/game.board
Content-Type: application/json

{
  "updates": [
    {
      "type": "callMethod",
      "payload": {
        "method": "makeMove",
        "params": [4]
      }
    }
  ]
}
```

---

#### 2. Answer Question

**Livewire responds with question:**

```json
{
  "effects": {
    "dispatched": [
      {
        "event": "show-question",
        "data": {
          "question": {
            "id": 127,
            "text": "Berapakah 5 × 8?",
            "answers": [
              {"id": 1, "text": "35"},
              {"id": 2, "text": "40"},
              {"id": 3, "text": "45"},
              {"id": 4, "text": "50"}
            ]
          }
        }
      }
    ]
  }
}
```

---

#### 3. Submit Answer

**Request:**

```
POST /livewire/message/jawab-soal

{
  "updates": [
    {
      "type": "callMethod",
      "payload": {
        "method": "submitAnswer",
        "params": [2]
      }
    }
  ]
}
```

**Response (Correct):**

```json
{
  "effects": {
    "dispatched": [
      {
        "event": "answer-correct",
        "data": {"position": 4}
      }
    ]
  },
  "serverMemo": {
    "data": {
      "allowMove": true
    }
  }
}
```

---

#### 4. Complete Move

**Backend processes:**

1. Records move in database
2. Updates match state
3. Broadcasts `MoveMade` event via Reverb

**Reverb Broadcast:**

```json
{
  "channel": "match.123",
  "event": "MoveMade",
  "data": {
    "position": 4,
    "symbol": "X",
    "user": {
      "id": 10,
      "name": "John Doe"
    },
    "match_id": 123
  }
}
```

**All clients subscribed to channel receive update automatically**

---

## Webhook Endpoints

### Payment Webhooks (Future)

#### Midtrans Notification

**Endpoint:** `POST /webhooks/midtrans`  
**Authentication:** Token verification  
**Payload:**

```json
{
  "transaction_status": "settlement",
  "order_id": "ORDER-123456",
  "gross_amount": "50000.00",
  "payment_type": "bank_transfer",
  "transaction_time": "2025-02-15 14:30:22"
}
```

**Response (200):**

```json
{
  "status": "success"
}
```

---

## Admin API (Filament)

Filament admin panel uses its own internal API (not exposed).

### Admin Routes

**Base URL:** `/admin`

**Protected by:** `auth`, `role:admin`

**Available Panels:**

- `/admin/users` - User management
- `/admin/tournaments` - Tournament management
- `/admin/questions` - Question bank
- `/admin/categories` - Category management
- `/admin/schools` - School management

**API calls handled internally by Filament Livewire components**

---

## Testing Endpoints

### Test Helpers

```php
// Authentication
$this->actingAs($user);

// Livewire component testing
Livewire::test(Board::class, ['match' => $match])
    ->call('makeMove', 4)
    ->assertDispatched('move-made');

// HTTP endpoint testing
$this->post('/tournaments', $data)
    ->assertRedirect('/tournaments/1')
    ->assertSessionHas('success');

// Broadcasting testing
Event::fake();
// ... perform action
Event::assertDispatched(MoveMade::class);
```

---

## API Versioning

**Current:** No versioning needed (internal Livewire API)

**Future Public API:**

```
/api/v1/tournaments
/api/v1/matches
```

**Versioning Strategy:**

- URI versioning (`/api/v1/`, `/api/v2/`)
- Accept header: `Accept: application/json; version=2`

---

## CORS Configuration

**Currently:** Not needed (same-origin)

**If external API needed:**

```php
// config/cors.php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => [env('FRONTEND_URL')],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

---

## Performance Considerations

### Caching

```php
// Cache categories (rarely change)
Route::get('/categories', function () {
    return Cache::remember('categories', 3600, function () {
        return Category::all();
    });
});
```

### Eager Loading

```php
// Prevent N+1 queries
$tournaments = Tournament::with('user', 'category', 'groups')->get();
```

### Pagination

```php
// Always paginate large datasets
$questions = Question::paginate(50);
```

---

## Security Best Practices

1. **CSRF Protection**: Automatic in Livewire
2. **XSS Prevention**: Blade escaping by default
3. **SQL Injection**: Eloquent parameterization
4. **Rate Limiting**: Applied to all endpoints
5. **Authentication**: Required for all protected routes
6. **Authorization**: Role-based (Spatie Permission)
7. **HTTPS Only**: Enforce in production

---

## Monitoring & Logging

### Telescope

**Endpoint:** `/telescope`  
**Access:** Development/staging only  
**Features:**

- Request logging
- Query monitoring
- Event tracking
- Job history

### Log Channels

```php
// Storage: storage/logs/laravel.log
Log::info('Tournament created', ['tournament_id' => $id]);
Log::error('Match error', ['error' => $e->getMessage()]);
```

---

## Indonesian Language Support

All error messages and responses support Bahasa Indonesia:

```php
// resources/lang/id/validation.php
'required' => ':attribute harus diisi.',
'email' => ':attribute harus berupa alamat email yang valid.',
'unique' => ':attribute sudah terdaftar.',
```

**Usage:**

```php
return back()->with('error', __('Turnamen tidak ditemukan'));
```

---

## API Documentation Tools

### Telescope (Internal)

Access at `/telescope` during development

### Postman Collection (Future)

Generate for external API testing

---

## Contact & Support

**Development Team:** OtakTangkas Developers  
**Email:** dev@otaktangkas.id  
**Documentation:** See `/docs` directory

---

## Changelog

**v2.0 (2026-07-03)**

- Updated stack to Laravel 13 / Livewire 4
- Replaced Pusher with Laravel Reverb (same protocol, self-hosted)
- Replaced Breeze with the official Livewire starter kit

**v1.0 (2025-02-15)**

- Initial documentation
- Livewire component endpoints
- Real-time channels
- Authentication flows

---

**Document Version:** 2.0  
**Maintained By:** OtakTangkas Development Team  
**Last Review:** 2026-07-03
