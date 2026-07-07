# Game Addition: Memory Match (Ingat Pasangan)

## Overview
Add **Memory Match** as the third playable game type, in both Practice (vs AI)
and Quick Play (real-time multiplayer). A 4×4 grid holds 8 face-down emoji
pairs. Answer a quiz question correctly to earn the right to flip **two cards
at once**; a matching pair is claimed for your side, and every flip — hit or
miss — is revealed to both players before hiding again. Most pairs wins.

This is the *pair-pick* memory variant: the classic "flip one, look, flip the
second" needs two moves per turn, which doesn't fit the shared quiz-gate flow
(one position per answered question). Picking a pair blind keeps the
`GameInterface` contract intact and still rewards memory — you learn the board
from every previous reveal, including your opponent's.

This document is the **tracking plan**. Each phase is one self-contained
commit that leaves the app releasable; the Status Log shows where to resume
if work is interrupted.

## Status Log

| Phase | Scope | Status | Commit |
|-------|-------|--------|--------|
| MM-1 | Game engine + AI | ✅ done | `a337bd0` |
| MM-2 | Practice mode integration | ✅ done | `67fdb98` |
| MM-3 | Quick Play integration | ✅ done | see git log |
| MM-4 | Closeout (docs sweep, full verify) | ⬜ pending | – |

**Resume rule:** find the first non-✅ phase, run `php artisan test` (and
`npm run build` from MM-2 on) to confirm a green baseline, then continue from
that phase's checklist. Each phase's commit hash is recorded by the following
phase's commit.

---

## Design Decisions (locked before implementation)

- **No migration needed**: `matches.game_type` already includes
  `memory_match` (reserved since Phase 1).
- **Board**: 16 cards (8 emoji pairs), shuffled server-side at match start.
  `board_state` JSON:
  ```json
  {
    "cards":    ["🍎", "🐘", ...],          // 16 shuffled values
    "matched":  {"3": "X", "9": "X"},       // claimed card index -> owner symbol
    "revealed": [3, 9, 12],                  // every index shown at least once
    "scores":   {"X": 1, "O": 0},
    "last_flip": {"cards": [5, 12], "by": "O", "matched": false}
  }
  ```
  Card values never reach the browser except through rendered Blade (claimed
  cards + the last flip), so peeking at the Livewire payload can't cheat.
- **Position format**: `"a|b"` — two distinct unclaimed card indexes,
  normalized to `min|max`. `match_moves.position` already stores free-form
  strings, and the components already validate any format via the engine's
  `getValidMoves()` (the CF-3 refactor paying off).
- **Turn flow**: a legal flip consumes the turn whether it matches or not
  (no "go again on match" — that would need `MatchService` changes and
  compounds badly with the quiz gate). Wrong quiz answer = retry, same as
  the other games.
- **Win condition**: first to 5 pairs clinches early (`player1_win` /
  `player2_win`); when all 8 pairs are claimed, higher score wins, 4–4 is a
  `draw`. Flows through the untouched `endMatch` rewards path.
- **AI (practice)**: remembers what has been revealed — not omniscient.
  `easy` = random legal pair; `medium` = 60% takes a known match (both
  halves previously revealed) else random; `hard` = always takes a known
  match, otherwise flips unrevealed cards to gain information.
- **UI**: two-tap selection handled by Alpine inside the board partial
  (first tap marks a card, second tap submits `"a|b"` via the existing
  `selectCell`) — zero component PHP changes for selection. Card backs use
  the brand gradient with a 🧠 mark; claimed cards stay face-up with the
  owner's ring color; the last flip shows face-up with an amber "just
  revealed" ring; reveals `pop-in` (value-keyed like the other boards).
  Score chips above the board (pasangan count per player).
- **Copy**: the kotak/kolom unit copy gains a "dua kartu" variant. All
  test-asserted strings stay unchanged.

---

## Phase MM-1: Game Engine + AI

`MemoryMatchGame` behind the existing `GameInterface`, registered in
`GameFactory`. Engine + AI + tests only, no UI.

### Checklist
- [x] `app/Services/GameEngine/Games/MemoryMatchGame.php`
  - [x] `initialize()`: 8 shuffled emoji pairs, empty matched/revealed/scores
  - [x] `makeMove()`: validates the pair (format, range, distinct,
        unclaimed), reveals both cards, claims + scores on a match, records
        `last_flip` and `revealed`; wrong answers make no move
  - [x] `checkGameOver()`: early clinch at 5 pairs, full-board compare,
        4–4 draw
  - [x] `getValidMoves()`: all `"a|b"` pairs (a < b) of unclaimed indexes
  - [x] `getAIMove()`: easy random / medium 60% known-match / hard
        known-match then prefer-unrevealed
  - [x] `getGameState()`: same shape as the other games
- [x] Register `memory_match` in `GameFactory`
- [x] Unit tests: pair distribution, claim + score, miss reveals without
      claiming, claimed/duplicate/malformed positions rejected, early
      clinch, draw, valid-move shrinkage, AI legality, hard AI takes a
      known match and prefers unrevealed cards otherwise
- [x] Feature check: `MatchService::createMatch` with
      `['game_type' => 'memory_match']` initializes a 16-card board through
      the existing enum column
- [x] Full suite green → commit `MM-1: Memory Match engine + AI`

## Phase MM-2: Practice Mode Integration

Play Memory Match vs the AI from `/practice`.

### Checklist
- [x] `Practice\Play::GAME_TYPES` gains `memory_match`; picker gains
      🧠 Memory (component logic otherwise untouched)
- [x] Board partial `board-memory-match`: 4×4 card grid, Alpine two-tap
      selection, card backs / claimed / just-revealed states, pop-in
      reveals, score chips (note: just-revealed cards stay clickable —
      they're legal picks for the next flip)
- [x] Unit copy: "dua kartu" variant in practice + match views
- [x] Component tests: picker deals a memory match, a correct answer with a
      matching pair claims it and scores, a miss reveals without claiming,
      wrong answer keeps the selection for retry
- [x] Browser verify (Playwright screenshots of a live memory practice game)
- [x] `npm run build` + full suite green → commit `MM-2: Memory Match
      practice mode`

## Phase MM-3: Quick Play Integration

### Checklist
- [x] Lobby picker gains a Memory preview card (three-game grid)
- [x] Matchmaking needs no code change (game_type filter already generic) —
      regression test proves memory seekers pair together and don't join
      other games' waiting matches
- [x] Multiplayer round-trip test on a memory board (host flips a pair and
      scores, guest flips a miss, turn passes back)
- [x] Two-browser Playwright verify (both players see the same reveal)
- [x] `npm run build` + full suite green → commit `MM-3: Memory Match quick
      play`

## Phase MM-4: Closeout

### Checklist
- [ ] README game list: Memory Match listed as available
- [ ] INDEX.md: link this document
- [ ] Status Log above fully ✅ with commit hashes
- [ ] Full suite + fresh browser sweep, push

---

## Out of Scope (this addition)
- Daily Puzzle scenarios for Memory Match (puzzle stays Tic-Tac-Toe)
- "Go again on match" turn rule (needs MatchService changes; revisit later)
- Memory-specific achievements
- Configurable board sizes

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
