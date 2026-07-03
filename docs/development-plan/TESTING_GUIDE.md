# TESTING GUIDE DOCUMENTATION

**OtakTangkas Platform - Comprehensive Testing Guide**  
**Version:** 2.0  
**Last Updated:** July 2026  
**Testing Framework:** Pest 4 (PHPUnit under the hood)  
**Browser Testing:** Pest 4 Browser Testing (Playwright-powered)

---

## Table of Contents

1. [Overview](#overview)
2. [Testing Environment Setup](#testing-environment-setup)
3. [Unit Testing](#unit-testing)
4. [Feature Testing](#feature-testing)
5. [Livewire Component Testing](#livewire-component-testing)
6. [Browser Testing (Pest 4)](#browser-testing-pest-4)
7. [Database Testing](#database-testing)
8. [Real-Time Testing](#real-time-testing)
9. [Performance Testing](#performance-testing)
10. [Security Testing](#security-testing)
11. [User Acceptance Testing](#user-acceptance-testing)
12. [Load Testing](#load-testing)
13. [Test Data Factories](#test-data-factories)
14. [Continuous Integration](#continuous-integration)
15. [Code Coverage](#code-coverage)
16. [Testing Best Practices](#testing-best-practices)

---

## Overview

### Testing Philosophy

**OtakTangkas follows the Testing Pyramid:**

```
        ┌─────────────┐
       /  E2E Tests   \     10% - Browser/Integration
      / (Pest Browser) \
     └─────────────────┘
    ┌───────────────────┐
   /  Feature Tests     \    30% - HTTP/Livewire
  /   (Pest/PHPUnit)     \
 └───────────────────────┘
┌─────────────────────────┐
│     Unit Tests          │  60% - Models/Services
│   (Pest/PHPUnit)        │
└─────────────────────────┘
```

### Testing Goals

- **Code Coverage:** Minimum 80% overall, 90% for critical paths
- **Test Speed:** Unit tests < 100ms, Feature tests < 500ms
- **Reliability:** Zero flaky tests in CI pipeline
- **Maintainability:** DRY tests with shared fixtures

### Test Types

| Type | Purpose | Tool | Speed |
|------|---------|------|-------|
| Unit | Test individual classes/methods | Pest/PHPUnit | Fast |
| Feature | Test HTTP endpoints | Pest/PHPUnit | Medium |
| Livewire | Test reactive components | Livewire Testing | Medium |
| Browser | Test full user flows | Pest 4 Browser (Playwright) | Slow |
| Performance | Test response times | k6/Artillery | Varies |
| Security | Test vulnerabilities | OWASP ZAP | Medium |

---

## Testing Environment Setup

### Installation

```bash
# Laravel 13 ships with Pest 4 by default — no installation needed.
# If migrating an older project:
composer require --dev pestphp/pest pestphp/pest-plugin-laravel

# Add browser testing (replaces Laravel Dusk)
composer require --dev pestphp/pest-plugin-browser

# Install the Playwright runtime used by browser tests
npm install playwright
npx playwright install chromium
```

### Environment Configuration

**`.env.testing`:**

```env
APP_ENV=testing
APP_KEY=base64:test_key_here
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array

MAIL_MAILER=log
BROADCAST_CONNECTION=log
```

**Why SQLite in-memory:**

- Fast (no disk I/O)
- Clean state per test
- No cleanup needed

### Database Setup

```bash
# Run migrations before tests (automatic with Pest)
php artisan migrate --env=testing

# Alternative: RefreshDatabase trait
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
```

### Pest Configuration

**`tests/Pest.php`:**

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Global helper functions
function actingAsStudent()
{
    return test()->actingAs(
        \App\Models\User::factory()
            ->create()
            ->assignRole('student')
    );
}

function actingAsTeacher()
{
    return test()->actingAs(
        \App\Models\User::factory()
            ->create()
            ->assignRole('teacher')
    );
}

function actingAsAdmin()
{
    return test()->actingAs(
        \App\Models\User::factory()
            ->create()
            ->assignRole('admin')
    );
}
```

---

## Unit Testing

### Testing Models

**`tests/Unit/Models/TournamentTest.php`:**

```php
<?php

use App\Models\Tournament;
use App\Models\User;
use App\Models\Category;

test('tournament belongs to user', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create(['user_id' => $user->id]);
    
    expect($tournament->user)->toBeInstanceOf(User::class)
        ->and($tournament->user->id)->toBe($user->id);
});

test('tournament has many groups', function () {
    $tournament = Tournament::factory()
        ->hasGroups(3)
        ->create();
    
    expect($tournament->groups)->toHaveCount(3);
});

test('tournament can be started', function () {
    $tournament = Tournament::factory()->create(['status' => 'draft']);
    
    $tournament->start();
    
    expect($tournament->status)->toBe('ongoing')
        ->and($tournament->fresh()->status)->toBe('ongoing');
});

test('tournament knows if it is ongoing', function () {
    $ongoingTournament = Tournament::factory()->create(['status' => 'ongoing']);
    $draftTournament = Tournament::factory()->create(['status' => 'draft']);
    
    expect($ongoingTournament->isOngoing())->toBeTrue()
        ->and($draftTournament->isOngoing())->toBeFalse();
});
```

---

### Testing Services

**`tests/Unit/Services/MatchServiceTest.php`:**

```php
<?php

use App\Services\MatchService;
use App\Models\Matches;
use App\Models\User;
use App\Models\Group;

beforeEach(function () {
    $this->service = app(MatchService::class);
    $this->match = Matches::factory()->create();
    $this->user = User::factory()->create();
});

test('can get board state', function () {
    $board = $this->service->getBoardState($this->match);
    
    expect($board)->toBeArray()
        ->toHaveCount(9)
        ->each->toBeNull(); // Empty board initially
});

test('validates position range', function () {
    $this->service->makeMove($this->match, $this->user, 10);
})->throws(Exception::class, 'Invalid position');

test('prevents move on taken position', function () {
    // Make first move
    $group = Group::factory()->create();
    $this->match->moves()->create([
        'user_id' => $this->user->id,
        'group_id' => $group->id,
        'symbol' => 'X',
        'position' => 4,
        'correct' => true,
    ]);
    
    // Try same position
    $this->service->makeMove($this->match, $this->user, 4);
})->throws(Exception::class, 'Position already taken');

test('detects horizontal win', function () {
    $group = Group::factory()->create();
    
    // Create winning pattern (top row)
    $this->match->moves()->createMany([
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'X', 'position' => 0, 'correct' => true],
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'X', 'position' => 1, 'correct' => true],
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'X', 'position' => 2, 'correct' => true],
    ]);
    
    $winner = $this->service->checkWinner($this->match);
    
    expect($winner)->toBeInstanceOf(Group::class)
        ->and($winner->id)->toBe($group->id);
});

test('detects diagonal win', function () {
    $group = Group::factory()->create();
    
    // Diagonal: 0, 4, 8
    $this->match->moves()->createMany([
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'O', 'position' => 0, 'correct' => true],
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'O', 'position' => 4, 'correct' => true],
        ['user_id' => $this->user->id, 'group_id' => $group->id, 'symbol' => 'O', 'position' => 8, 'correct' => true],
    ]);
    
    $winner = $this->service->checkWinner($this->match);
    
    expect($winner)->not->toBeNull();
});
```

---

### Testing Utilities

**`tests/Unit/Helpers/BoardHelperTest.php`:**

```php
<?php

use App\Helpers\BoardHelper;

test('converts position to coordinates', function () {
    expect(BoardHelper::positionToCoords(0))->toBe([0, 0])
        ->and(BoardHelper::positionToCoords(4))->toBe([1, 1])
        ->and(BoardHelper::positionToCoords(8))->toBe([2, 2]);
});

test('validates board state', function () {
    $validBoard = ['X', 'O', 'X', null, 'O', null, null, null, null];
    $invalidBoard = ['X', 'O', 'K']; // Invalid symbol
    
    expect(BoardHelper::isValidBoard($validBoard))->toBeTrue()
        ->and(BoardHelper::isValidBoard($invalidBoard))->toBeFalse();
});
```

---

## Feature Testing

### Authentication Tests

**`tests/Feature/Auth/AuthenticationTest.php`:**

```php
<?php

use App\Models\User;

test('login page can be rendered', function () {
    $response = $this->get('/login');
    
    $response->assertStatus(200);
});

test('users can authenticate with email', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
    
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});

test('users can authenticate with nomor induk', function () {
    $user = User::factory()->create([
        'nomor_induk' => '123456789',
        'password' => bcrypt('password123'),
    ]);
    
    $response = $this->post('/login', [
        'nomor_induk' => '123456789',
        'password' => 'password123',
    ]);
    
    $this->assertAuthenticated();
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();
    
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)->post('/logout');
    
    $this->assertGuest();
});
```

---

### Tournament Tests

**`tests/Feature/TournamentTest.php`:**

```php
<?php

use App\Models\Tournament;
use App\Models\User;
use App\Models\Category;

test('teacher can create tournament', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $category = Category::factory()->create();
    
    $response = actingAsTeacher()->post('/tournaments', [
        'name' => 'Turnamen Matematika',
        'category_id' => $category->id,
        'groups' => [
            [
                'name' => 'Tim A',
                'members' => [1, 2, 3],
            ],
            [
                'name' => 'Tim B',
                'members' => [4, 5, 6],
            ],
        ],
    ]);
    
    $response->assertRedirect();
    expect(Tournament::count())->toBe(1);
});

test('student cannot create tournament', function () {
    $category = Category::factory()->create();
    
    $response = actingAsStudent()->post('/tournaments', [
        'name' => 'Turnamen Test',
        'category_id' => $category->id,
    ]);
    
    $response->assertForbidden();
});

test('tournament requires valid category', function () {
    $response = actingAsTeacher()->post('/tournaments', [
        'name' => 'Turnamen Test',
        'category_id' => 999, // Non-existent
    ]);
    
    $response->assertSessionHasErrors('category_id');
});

test('tournament can be started by creator', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $tournament = Tournament::factory()->create(['user_id' => $teacher->id]);
    
    $response = $this->actingAs($teacher)
        ->post("/tournaments/{$tournament->id}/start");
    
    $response->assertRedirect();
    expect($tournament->fresh()->status)->toBe('ongoing');
});

test('tournament cannot be started by non-creator', function () {
    $teacher1 = User::factory()->create()->assignRole('teacher');
    $teacher2 = User::factory()->create()->assignRole('teacher');
    $tournament = Tournament::factory()->create(['user_id' => $teacher1->id]);
    
    $response = $this->actingAs($teacher2)
        ->post("/tournaments/{$tournament->id}/start");
    
    $response->assertForbidden();
});
```

---

### Question Tests

**`tests/Feature/QuestionTest.php`:**

```php
<?php

use App\Models\Question;
use App\Models\Answer;
use App\Models\Category;

test('teacher can create question with answers', function () {
    $category = Category::factory()->create();
    
    $response = actingAsTeacher()->post('/kelola-soal', [
        'text' => 'Berapakah 2 + 2?',
        'category_id' => $category->id,
        'answers' => [
            ['text' => '3', 'is_correct' => false],
            ['text' => '4', 'is_correct' => true],
            ['text' => '5', 'is_correct' => false],
            ['text' => '6', 'is_correct' => false],
        ],
    ]);
    
    $response->assertRedirect();
    
    expect(Question::count())->toBe(1)
        ->and(Answer::count())->toBe(4)
        ->and(Answer::where('is_correct', true)->count())->toBe(1);
});

test('question requires at least 2 answers', function () {
    $category = Category::factory()->create();
    
    $response = actingAsTeacher()->post('/kelola-soal', [
        'text' => 'Question text',
        'category_id' => $category->id,
        'answers' => [
            ['text' => 'Answer 1', 'is_correct' => true],
        ],
    ]);
    
    $response->assertSessionHasErrors('answers');
});

test('question requires exactly one correct answer', function () {
    $category = Category::factory()->create();
    
    $response = actingAsTeacher()->post('/kelola-soal', [
        'text' => 'Question text',
        'category_id' => $category->id,
        'answers' => [
            ['text' => 'Answer 1', 'is_correct' => true],
            ['text' => 'Answer 2', 'is_correct' => true], // Two correct!
            ['text' => 'Answer 3', 'is_correct' => false],
        ],
    ]);
    
    $response->assertSessionHasErrors();
});

test('questions can be exported', function () {
    Question::factory()->count(10)->create();
    
    $response = actingAsTeacher()->get('/kelola-soal/export');
    
    $response->assertStatus(200)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
```

---

## Livewire Component Testing

### Board Component Tests

**`tests/Feature/Livewire/BoardTest.php`:**

```php
<?php

use App\Livewire\Game\Board;
use App\Models\Matches;
use App\Models\User;
use App\Models\Group;
use Livewire\Livewire;

test('board component renders', function () {
    $match = Matches::factory()->create();
    
    Livewire::actingAs(User::factory()->create())
        ->test(Board::class, ['match' => $match])
        ->assertStatus(200)
        ->assertSee('Current Turn');
});

test('board shows correct initial state', function () {
    $match = Matches::factory()->create();
    
    Livewire::test(Board::class, ['match' => $match])
        ->assertSet('board', array_fill(0, 9, null));
});

test('player can make move on valid position', function () {
    $match = Matches::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    
    // Add user to group
    $group->members()->attach($user->id);
    $match->update(['current_turn_group_id' => $group->id]);
    
    Livewire::actingAs($user)
        ->test(Board::class, ['match' => $match])
        ->call('makeMove', 4)
        ->assertDispatched('move-made');
    
    expect($match->fresh()->moves)->toHaveCount(1);
});

test('player cannot move on taken position', function () {
    $match = Matches::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    
    // Create existing move
    $match->moves()->create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'symbol' => 'X',
        'position' => 4,
        'correct' => true,
    ]);
    
    Livewire::actingAs($user)
        ->test(Board::class, ['match' => $match])
        ->call('makeMove', 4)
        ->assertDispatched('error');
});

test('board updates on real-time event', function () {
    $match = Matches::factory()->create();
    
    $component = Livewire::test(Board::class, ['match' => $match]);
    
    // Simulate real-time event
    $component->dispatch('echo:match.' . $match->id . ',MoveMade', [
        'position' => 4,
        'symbol' => 'X',
    ]);
    
    $component->assertMethodWasCalled('onMoveMade');
});
```

---

### Tournament List Component Tests

**`tests/Feature/Livewire/TournamentListTest.php`:**

```php
<?php

use App\Livewire\Tournament\TournamentList;
use App\Models\Tournament;
use Livewire\Livewire;

test('tournament list renders', function () {
    Tournament::factory()->count(5)->create();
    
    Livewire::test(TournamentList::class)
        ->assertStatus(200)
        ->assertSee('Tournaments');
});

test('can filter tournaments by status', function () {
    Tournament::factory()->create(['status' => 'ongoing']);
    Tournament::factory()->create(['status' => 'completed']);
    
    Livewire::test(TournamentList::class)
        ->set('status', 'ongoing')
        ->assertSee('ongoing')
        ->assertDontSee('completed');
});

test('can search tournaments by name', function () {
    Tournament::factory()->create(['name' => 'Matematika Championship']);
    Tournament::factory()->create(['name' => 'Fisika Tournament']);
    
    Livewire::test(TournamentList::class)
        ->set('search', 'Matematika')
        ->assertSee('Matematika Championship')
        ->assertDontSee('Fisika Tournament');
});

test('pagination works', function () {
    Tournament::factory()->count(25)->create();
    
    Livewire::test(TournamentList::class)
        ->set('perPage', 10)
        ->assertViewHas('tournaments', function ($tournaments) {
            return $tournaments->count() === 10;
        });
});
```

---

### Question Answer Component Tests

**`tests/Feature/Livewire/JawabSoalTest.php`:**

```php
<?php

use App\Livewire\JawabSoal;
use App\Models\Question;
use App\Models\Answer;
use Livewire\Livewire;

test('question displays with answers', function () {
    $question = Question::factory()
        ->has(Answer::factory()->count(4))
        ->create();
    
    Livewire::test(JawabSoal::class, ['questionId' => $question->id])
        ->assertSee($question->text)
        ->assertSee($question->answers->first()->answer_text);
});

test('correct answer allows move', function () {
    $question = Question::factory()->create();
    $correctAnswer = Answer::factory()->create([
        'question_id' => $question->id,
        'is_correct' => true,
    ]);
    
    Livewire::test(JawabSoal::class, ['questionId' => $question->id])
        ->call('submitAnswer', $correctAnswer->id)
        ->assertDispatched('answer-correct')
        ->assertSet('allowMove', true);
});

test('wrong answer increments counter', function () {
    $question = Question::factory()->create();
    $wrongAnswer = Answer::factory()->create([
        'question_id' => $question->id,
        'is_correct' => false,
    ]);
    
    Livewire::test(JawabSoal::class, ['questionId' => $question->id])
        ->call('submitAnswer', $wrongAnswer->id)
        ->assertDispatched('answer-incorrect')
        ->assertSet('wrongAnswers', 1);
});

test('three wrong answers skip turn', function () {
    $question = Question::factory()->create();
    $wrongAnswer = Answer::factory()->create([
        'question_id' => $question->id,
        'is_correct' => false,
    ]);
    
    $component = Livewire::test(JawabSoal::class, ['questionId' => $question->id]);
    
    // Submit wrong answer 3 times
    $component->call('submitAnswer', $wrongAnswer->id)
        ->call('submitAnswer', $wrongAnswer->id)
        ->call('submitAnswer', $wrongAnswer->id)
        ->assertDispatched('turn-skipped');
});
```

---

## Browser Testing (Pest 4)

Pest 4 ships first-class browser testing powered by **Playwright** — no separate Dusk suite, no ChromeDriver management. Browser tests live alongside regular Pest tests, use the full Laravel testing API (factories, `RefreshDatabase`, fakes), auto-wait for elements, and can run in parallel.

### Setup

```bash
composer require --dev pestphp/pest-plugin-browser
npm install playwright
npx playwright install chromium
```

### Complete User Flow Test

**`tests/Browser/TournamentFlowTest.php`:**

```php
<?php

use App\Models\User;
use App\Models\Category;

it('allows a teacher to create and start a tournament', function () {
    $teacher = User::factory()->create()->assignRole('teacher');
    $category = Category::factory()->create();

    $this->actingAs($teacher);

    $page = visit('/tournaments/create');

    $page->assertSee('Create Tournament')
        ->fill('name', 'Test Tournament')
        ->select('category_id', $category->id)
        ->press('Add Group')
        ->fill('groups[0][name]', 'Team Alpha')
        ->press('Add Member')
        ->fill('groups[0][members][0]', 'Student 1')
        ->press('Save Tournament')
        ->assertPathContains('/tournaments/')
        ->assertSee('Tournament created successfully');
});

it('allows a student to join a match and make a move', function () {
    $student = User::factory()->create()->assignRole('student');
    $match = Matches::factory()->create();

    $this->actingAs($student);

    $page = visit("/tournaments/{$match->tournament_id}/matches/{$match->id}");

    // Assertions auto-wait for the element/text to appear (Playwright)
    $page->assertSee('Join Match')
        ->press('Join Match')
        ->assertSee('Your Turn')
        ->click('@board-cell-4') // Click middle cell
        ->assertSee('Answer Question')
        ->click('@answer-2')     // Click correct answer
        ->assertSee('Correct!')
        ->assertSee('X');        // Move placed
});

it('updates the board in real time for the other player', function () {
    [$user1, $user2] = User::factory()->count(2)->create();
    $match = Matches::factory()->create();
    $url = "/tournaments/{$match->tournament_id}/matches/{$match->id}";

    // Two independent browser pages in one test
    $this->actingAs($user1);
    $first = visit($url);

    $this->actingAs($user2);
    $second = visit($url);

    // User 1 makes a move
    $first->click('@board-cell-0')
        ->assertSee('Answer Question')
        ->click('@answer-correct');

    // User 2 sees the update in real time (auto-waits)
    $second->assertSee('X')
        ->assertSee("Team Alpha's Turn");
});
```

> **Tip:** Pest 4 browser tests also support device simulation (`->on()->mobile()`), color-scheme simulation, visual regression snapshots, and `->assertNoJavascriptErrors()` / `->assertNoConsoleLogs()` for smoke-testing pages.

---

## Database Testing

### Using Factories

**`database/factories/TournamentFactory.php`:**

```php
<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'category_id' => Category::factory(),
            'status' => 'draft',
        ];
    }
    
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ongoing',
        ]);
    }
    
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
```

### Using Seeders in Tests

```php
test('can retrieve all categories', function () {
    $this->seed(CategorySeeder::class);
    
    $categories = Category::all();
    
    expect($categories)->toHaveCount(5)
        ->and($categories->pluck('name')->toArray())->toContain('Matematika');
});
```

---

## Real-Time Testing

### Testing Events

**`tests/Feature/Events/MatchEventTest.php`:**

```php
<?php

use App\Events\MoveMade;
use App\Models\Matches;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('move made event is dispatched', function () {
    Event::fake([MoveMade::class]);
    
    $match = Matches::factory()->create();
    $user = User::factory()->create();
    
    // Perform action that should dispatch event
    app(\App\Services\MatchService::class)->makeMove($match, $user, 4);
    
    Event::assertDispatched(MoveMade::class, function ($event) use ($match, $user) {
        return $event->match->id === $match->id
            && $event->user->id === $user->id
            && $event->position === 4;
    });
});

test('match ended event broadcasts to correct channel', function () {
    Event::fake();
    
    $match = Matches::factory()->create();
    $winner = Group::factory()->create();
    
    event(new \App\Events\MatchEnded($match, $winner));
    
    Event::assertDispatched(\App\Events\MatchEnded::class, function ($event) {
        return $event->broadcastOn()->name === 'match.' . $event->match->id;
    });
});
```

---

## Performance Testing

### Response Time Testing

**`tests/Performance/ResponseTimeTest.php`:**

```php
<?php

use App\Models\User;
use App\Models\Tournament;

test('dashboard loads in under 500ms', function () {
    $user = User::factory()->create();
    
    $start = microtime(true);
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $duration = (microtime(true) - $start) * 1000; // Convert to ms
    
    expect($duration)->toBeLessThan(500)
        ->and($response->status())->toBe(200);
});

test('tournament list with 100 items loads in under 1 second', function () {
    Tournament::factory()->count(100)->create();
    $user = User::factory()->create();
    
    $start = microtime(true);
    
    $response = $this->actingAs($user)->get('/tournaments');
    
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration)->toBeLessThan(1000);
});
```

### Query Performance

```php
test('tournament list uses eager loading', function () {
    Tournament::factory()->count(10)->create();
    
    \DB::enableQueryLog();
    
    $this->actingAs(User::factory()->create())
        ->get('/tournaments');
    
    $queries = \DB::getQueryLog();
    
    // Should be 3 queries max (users, tournaments, categories)
    expect(count($queries))->toBeLessThanOrEqual(3);
});
```

---

## Security Testing

### OWASP Top 10 Tests

#### 1. SQL Injection Prevention

```php
test('search input is protected from SQL injection', function () {
    $maliciousInput = "'; DROP TABLE users; --";
    
    $response = $this->actingAs(User::factory()->create())
        ->get('/tournaments?search=' . urlencode($maliciousInput));
    
    $response->assertStatus(200);
    
    // Table should still exist
    expect(\Schema::hasTable('users'))->toBeTrue();
});
```

#### 2. XSS Prevention

```php
test('user input is escaped in output', function () {
    $xssScript = '<script>alert("XSS")</script>';
    
    $tournament = Tournament::factory()->create(['name' => $xssScript]);
    
    $response = $this->get('/tournaments');
    
    $response->assertDontSee($xssScript, false) // false = don't escape
        ->assertSee(htmlspecialchars($xssScript)); // Should be escaped
});
```

#### 3. CSRF Protection

```php
test('POST requests require CSRF token', function () {
    $response = $this->post('/tournaments', [
        'name' => 'Test Tournament',
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
});
```

#### 4. Authentication Required

```php
test('protected routes require authentication', function () {
    $response = $this->get('/dashboard');
    
    $response->assertRedirect('/login');
});
```

#### 5. Authorization Check

```php
test('students cannot access admin pages', function () {
    $student = User::factory()->create()->assignRole('student');
    
    $response = $this->actingAs($student)->get('/admin/users');
    
    $response->assertForbidden();
});
```

---

## User Acceptance Testing

### Test Scenarios

**`tests/Acceptance/TournamentManagementTest.md`:**

```markdown
# Tournament Management UAT

## Scenario 1: Teacher Creates Tournament

**Given:** Logged in as teacher
**When:** Navigate to "Create Tournament"
**And:** Fill form with valid data
**And:** Add 2 groups with 3 members each
**And:** Click "Save"
**Then:** Tournament is created
**And:** Redirected to tournament view
**And:** Success message displayed

## Scenario 2: Student Joins Match

**Given:** Logged in as student
**And:** Tournament is ongoing
**When:** Navigate to match page
**And:** Click "Join Match"
**Then:** Student added to spectators
**And:** Real-time board visible
**And:** Can see current turn indicator

## Scenario 3: Make Move with Question

**Given:** It's user's turn
**When:** Click empty cell
**Then:** Question modal appears
**When:** Select correct answer
**Then:** Symbol placed on board
**And:** Turn switches to opponent
**When:** Select wrong answer 3 times
**Then:** Turn skipped automatically
```

---

## Load Testing

### Using k6

**`tests/Load/tournament-load.js`:**

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    stages: [
        { duration: '2m', target: 100 }, // Ramp up to 100 users
        { duration: '5m', target: 100 }, // Stay at 100 users
        { duration: '2m', target: 200 }, // Ramp up to 200 users
        { duration: '5m', target: 200 }, // Stay at 200 users
        { duration: '2m', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests under 500ms
        http_req_failed: ['rate<0.01'],   // Less than 1% failures
    },
};

const BASE_URL = 'https://otaktangkas.test';

export default function () {
    // Login
    let loginRes = http.post(`${BASE_URL}/login`, {
        email: 'test@example.com',
        password: 'password',
    });
    
    check(loginRes, {
        'login successful': (r) => r.status === 200,
    });
    
    sleep(1);
    
    // View tournaments
    let tournamentsRes = http.get(`${BASE_URL}/tournaments`);
    
    check(tournamentsRes, {
        'tournaments loaded': (r) => r.status === 200,
        'response time OK': (r) => r.timings.duration < 500,
    });
    
    sleep(2);
}
```

**Run Load Test:**

```bash
k6 run tests/Load/tournament-load.js
```

---

## Test Data Factories

### Complete Factory Examples

**`database/factories/MatchesFactory.php`:**

```php
<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchesFactory extends Factory
{
    public function definition(): array
    {
        $tournament = Tournament::factory()->create();
        $group1 = Group::factory()->create(['tournament_id' => $tournament->id]);
        $group2 = Group::factory()->create(['tournament_id' => $tournament->id]);
        
        return [
            'tournament_id' => $tournament->id,
            'group1_id' => $group1->id,
            'group2_id' => $group2->id,
            'turn_number' => 0,
            'current_turn_group_id' => $group1->id,
        ];
    }
    
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'turn_number' => $this->faker->numberBetween(1, 5),
        ]);
    }
    
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'turn_number' => 9,
                'winner_id' => $attributes['group1_id'],
            ];
        });
    }
}
```

---

## Continuous Integration

### GitHub Actions Workflow

**`.github/workflows/tests.yml`:**

```yaml
name: Run Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: otaktangkas_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, json, bcmath
          coverage: xdebug
      
      - name: Copy .env
        run: php -r "file_exists('.env') || copy('.env.example', '.env');"
      
      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader
      
      - name: Generate key
        run: php artisan key:generate
      
      - name: Directory Permissions
        run: chmod -R 777 storage bootstrap/cache
      
      - name: Run Migrations
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: otaktangkas_test
          DB_USERNAME: root
          DB_PASSWORD: password
        run: php artisan migrate --force
      
      - name: Run Unit Tests
        run: php artisan test --parallel --testsuite=Unit
      
      - name: Run Feature Tests
        run: php artisan test --parallel --testsuite=Feature
      
      - name: Generate Coverage Report
        run: php artisan test --coverage --min=80
      
      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          token: ${{ secrets.CODECOV_TOKEN }}
          files: ./coverage.xml
```

---

## Code Coverage

### Generate Coverage Report

```bash
# With Pest
php artisan test --coverage

# With PHPUnit
./vendor/bin/phpunit --coverage-html coverage

# Minimum coverage threshold
php artisan test --coverage --min=80
```

### Coverage Goals

| Component | Target Coverage |
|-----------|----------------|
| Models | 95% |
| Services | 90% |
| Controllers | 85% |
| Livewire Components | 85% |
| Helpers | 90% |
| Overall | 80% |

---

## Testing Best Practices

### 1. Follow AAA Pattern

```php
test('example test', function () {
    // Arrange
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create();
    
    // Act
    $result = $tournament->addParticipant($user);
    
    // Assert
    expect($result)->toBeTrue()
        ->and($tournament->participants)->toContain($user);
});
```

### 2. Use Descriptive Test Names

```php
// Good
test('teacher can create tournament with multiple groups')

// Bad
test('test1')
```

### 3. One Assertion Per Test (When Possible)

```php
// Good
test('tournament has name')
test('tournament has status')
test('tournament has category')

// Acceptable (related assertions)
test('tournament has required attributes', function () {
    $tournament = Tournament::factory()->create();
    
    expect($tournament->name)->not->toBeNull()
        ->and($tournament->status)->toBe('draft')
        ->and($tournament->category)->toBeInstanceOf(Category::class);
});
```

### 4. Use Factories Instead of Manual Creation

```php
// Good
$user = User::factory()->create();

// Bad
$user = new User();
$user->name = 'Test User';
$user->email = 'test@example.com';
$user->save();
```

### 5. Clean Up After Tests

```php
// Use RefreshDatabase trait
uses(RefreshDatabase::class);

// Or clean up manually
afterEach(function () {
    Cache::flush();
    Event::fake();
});
```

---

## Troubleshooting Tests

### Common Issues

#### Tests Running Slowly

```bash
# Run tests in parallel
php artisan test --parallel

# Increase parallel processes
php artisan test --parallel --processes=8
```

#### Database Issues

```bash
# Clear and migrate
php artisan migrate:fresh --env=testing

# Check database connection
php artisan db:show --env=testing
```

#### Cache Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Testing Checklist

**Before Committing:**

- [ ] All tests pass locally
- [ ] No skipped tests without justification
- [ ] Code coverage meets minimum (80%)
- [ ] New features have tests
- [ ] Bug fixes have regression tests
- [ ] Livewire components tested
- [ ] Real-time events tested
- [ ] Database queries optimized
- [ ] No N+1 query issues
- [ ] Security tests pass

---

## References

- [Pest PHP Documentation](https://pestphp.com)
- [Pest Browser Testing](https://pestphp.com/docs/browser-testing)
- [Laravel Testing](https://laravel.com/docs/13.x/testing)
- [Livewire Testing](https://livewire.laravel.com/docs/testing)
- [k6 Load Testing](https://k6.io/docs/)

---

**Document Version:** 2.0  
**Maintained By:** OtakTangkas Development Team  
**Last Review:** 2026-07-03
