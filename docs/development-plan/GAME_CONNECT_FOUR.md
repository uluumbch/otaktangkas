# Game Addition: Connect Four (Empat Sejajar)

## Overview
Add **Connect Four** as the second playable game type, in both Practice (vs AI) and Quick Play (real-time multiplayer). Players answer a quiz question correctly to earn the right to drop a disc into a column; first to connect four discs (horizontal, vertical, or diagonal) wins.

This document is the **tracking plan** for the addition. Each phase is one
self-contained commit that leaves the app releasable. If work is interrupted,
the Status Log below shows exactly where to resume.

## Status Log

| Phase | Scope | Status | Commit |
|-------|-------|--------|--------|
| CF-1 | Game engine + migration | ✅ done | `see git log` |
| CF-2 | AI opponent | ⬜ pending | – |
| CF-3 | Practice mode integration | ⬜ pending | – |
| CF-4 | Quick Play integration | ⬜ pending | – |
| CF-5 | Closeout (docs sweep, full verify) | ⬜ pending | – |

**Resume rule:** find the first non-✅ phase, re-run `php artisan test` to
confirm the baseline is green, then continue from that phase's checklist.

---

## Design Decisions (locked before implementation)

- **Board**: 6 rows × 7 columns, stored in the existing `matches.board_state`
  JSON as `board[row][col]` with `''` / `'X'` / `'O'` — same cell vocabulary as
  Tic-Tac-Toe, so `MatchService`, rewards, events, and stats are untouched.
  Row `0` is the top; discs fall to the highest-index empty row (gravity).
- **Position format**: the column index as a string (`"0"`…`"6"`). The
  `match_moves.position` column already stores free-form strings.
- **Move legality is the engine's job**: the Livewire components stop parsing
  positions themselves and instead validate against
  `GameFactory::create($match->game_type)->getValidMoves($match)`. This is the
  one shared refactor, and it is what makes every future game type plug in
  without touching the components again.
- **Schema**: widen the `matches.game_type` enum to include `connect_four`
  (single migration; SQLite rebuilds the check constraint, MySQL alters the
  enum in place).
- **Matchmaking**: `findQuickPlayMatch` gains a `game_type` filter so players
  are only paired into the game they chose. **Without this, a Connect Four
  player could be seated at a Tic-Tac-Toe board** — CF-4 includes a regression
  test for exactly this.
- **UI**: game-specific board Blade partials
  (`livewire/partials/board-tic-tac-toe.blade.php`,
  `livewire/partials/board-connect-four.blade.php`) included by `game_type`;
  Connect Four is clicked per **column**, discs render as filled circles
  (primary = X, secondary = O).
- **AI (practice)**: `easy` = random valid column; `medium` = 70 % optimal /
  30 % random (same policy as Tic-Tac-Toe); `hard` = win now → block
  opponent's win → avoid columns that hand the opponent a win on the next
  drop → prefer center-out (`3, 2, 4, 1, 5, 0, 6`).
- **Rewards/progression**: unchanged — Connect Four matches flow through the
  same `endMatch` path (XP, coins, streaks, achievements, rank sync).

---

## Phase CF-1: Game Engine

New `ConnectFourGame` behind the existing `GameInterface`, registered in
`GameFactory`, with the enum migration. No UI yet — engine + tests only.

### Checklist
- [x] Migration: add `connect_four` to `matches.game_type` enum
- [x] `app/Services/GameEngine/Games/ConnectFourGame.php`
  - [x] `initialize()`: 6×7 empty board
  - [x] `makeMove()`: gravity drop into a column; rejects wrong answers,
        invalid columns, and full columns
  - [x] `checkGameOver()`: four-in-a-row horizontal / vertical / both
        diagonals; `draw` when the board fills
  - [x] `getValidMoves()`: non-full columns as `"0"`…`"6"`
  - [x] `getGameState()`: same shape as Tic-Tac-Toe's
- [x] Register `connect_four` in `GameFactory`
- [x] Unit tests (`tests/Unit/ConnectFourGameTest.php`): gravity stacking,
      wrong answer makes no move, full column rejected, all four win
      directions, draw, valid-move list shrinks as columns fill
- [x] Feature check: `MatchService::createMatch` with
      `['game_type' => 'connect_four']` initializes a 6×7 board
- [x] Full suite green → commit `CF-1: Connect Four engine`

## Phase CF-2: AI Opponent

`getAIMove()` for the three practice difficulties.

### Checklist
- [ ] `easy`: random valid column
- [ ] `medium`: 70 % optimal / 30 % random
- [ ] `hard`: win-now → block → don't gift a win (skip columns where the
      opponent wins by dropping on top of our disc) → center-out preference
- [ ] Tests: AI takes an immediate win, blocks an immediate loss, avoids the
      gift column when a safe column exists, easy mode still returns a legal
      move on a crowded board
- [ ] Full suite green → commit `CF-2: Connect Four AI`

## Phase CF-3: Practice Mode Integration

Play Connect Four vs the AI from `/practice`.

### Checklist
- [ ] `Practice\Play`: `gameType` property + picker UI (Tic-Tac-Toe ▸ Connect
      Four); new game starts a match of the chosen type
- [ ] Shared refactor: `selectCell()` validates via the engine's
      `getValidMoves()` instead of parsing `"row,col"` (keeps Tic-Tac-Toe
      behaviour identical — covered by the existing tests)
- [ ] Board partials extracted: `board-tic-tac-toe` (existing markup moved),
      `board-connect-four` (7 clickable columns, 6×7 disc grid)
- [ ] AI thinking delay + turn timer + timeout forfeit work unchanged
- [ ] Component tests: picker switches game type, a correct answer drops a
      disc to the bottom of the chosen column, wrong answer keeps the turn
- [ ] Browser verify (Playwright screenshot of a live Connect Four practice
      game)
- [ ] Full suite green → commit `CF-3: Connect Four practice mode`

## Phase CF-4: Quick Play Integration

Real-time multiplayer Connect Four with game-aware matchmaking.

### Checklist
- [ ] `findQuickPlayMatch()` filters on `game_type`; `Lobby` passes the
      player's chosen game into both find and create
- [ ] Lobby UI: game picker alongside the existing Play button
- [ ] `Game\Play` view includes the board partial by `game_type` (multiplayer
      component logic already game-agnostic after CF-3's refactor)
- [ ] Tests: two players choosing Connect Four get paired; a Connect Four
      seeker does **not** join a waiting Tic-Tac-Toe match (regression);
      full multiplayer round-trip on a Connect Four board
- [ ] Two-browser Playwright verify (both players see the same discs live)
- [ ] Full suite green → commit `CF-4: Connect Four quick play`

## Phase CF-5: Closeout

### Checklist
- [ ] README game list: Connect Four listed as available (Memory Match stays
      "planned")
- [ ] INDEX.md: link this document
- [ ] Status Log above fully ✅ with commit hashes
- [ ] Full suite + fresh browser sweep, push

---

## Out of Scope (this addition)
- Daily Puzzle scenarios for Connect Four (puzzle stays Tic-Tac-Toe)
- Connect Four-specific achievements
- Memory Match (tracked separately; enum slot already reserved)

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
