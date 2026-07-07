# UI Polish: Make It Feel Like a Game

## Overview
The functional UI reads as an admin dashboard: flat gray backdrop, plain
white cards, form-style buttons, no motion, no celebration. This plan turns
it into an **arcade quiz** experience without touching game logic: a playful
design system, tactile boards with animation, a real match "stage" (VS
header, celebrations), and a lobby that invites play.

This document is the **tracking plan**. Each phase is one self-contained
commit that leaves the app releasable; the Status Log shows where to resume
if work is interrupted.

## Status Log

| Phase | Scope | Status | Commit |
|-------|-------|--------|--------|
| UI-1 | Game design system (theme, animations, layout/nav) | ✅ done | `62a063c` |
| UI-2 | Board & gameplay feel (tiles, discs, motion, feedback) | ✅ done | `40ac063` |
| UI-3 | Match stage (VS header, celebrations, AI bubble) | ✅ done | `eceff05` |
| UI-4 | Lobby, dashboard & puzzle alignment | ✅ done | `ed3d0fb` |
| UI-5 | Verification sweep + closeout | ✅ done | see git log |

**Resume rule:** find the first non-✅ phase, run `php artisan test` and
`npm run build` to confirm a green baseline, then continue from that phase's
checklist. Each phase's commit hash is recorded by the following phase's
commit.

---

## Design Direction (locked before implementation)

- **Mood**: bright arcade quiz — energetic, rounded, chunky, Indonesian-market
  friendly. Light theme (readability on cheap Android screens), but with a
  colorful gradient backdrop instead of flat gray.
- **Palette**: keep the existing `primary` (sky) / `secondary` (fuchsia)
  brand tokens; add accent usage (amber for coins/rewards, green/red for
  right/wrong) through utilities — no new token families needed.
- **Typography**: system stack stays (no remote fonts — offline builds);
  game feel comes from weight (`font-black`), size jumps, and tight tracking
  on headings.
- **Tactility**: primary actions become "pressed-style" chunky buttons
  (solid bottom edge via `shadow`/`border-b-4`, `active:translate-y`),
  cards get soft colored shadows instead of `shadow-xs`.
- **Motion (CSS-only, no new JS)**: `@theme` keyframes compiled by Tailwind 4
  — `pop-in` (placed symbols), `disc-drop` (falling discs), `shake` (wrong
  answer), `pulse-glow` (active turn / low timer), `float` (lobby emoji),
  `confetti-fall` (win celebration). Animations trigger on element
  *insertion*: each board cell's mark is wrapped in a `wire:key` that
  includes its value, so Livewire's morph inserts a fresh node when a cell
  changes and the CSS animation plays exactly once.
- **Guardrails**: game logic, routes, component PHP, and all test-asserted
  copy (e.g. "Mode Latihan", "Menunggu lawan", "Puzzle Harian", "Mulai
  Puzzle", "Salah, coba lagi", "Waktu Habis", "Puzzle Selesai!", "Peringkat
  Hari Ini", "Terbuka ✓") stay unchanged. Views and CSS only. Every phase
  ends with the full suite + `npm run build` green.

---

## Phase UI-1: Game Design System

Foundation everything else uses.

### Checklist
- [x] `resources/css/app.css`: add keyframes (`pop-in`, `disc-drop`,
      `shake`, `pulse-glow`, `float`, `confetti-fall`) + `--animate-*`
      theme tokens; game shadow tokens
- [x] Component classes: `.btn-game` (chunky pressed button, primary and
      white variants), `.chip-hud` (nav/stat chips)
- [x] App layout: gradient playfield backdrop (soft primary→secondary wash),
      nav polish — gradient logo text, HUD-style level/coin chips, bolder
      active link pill
- [x] Guest layout aligned with the same system
- [x] `npm run build` + full suite green → commit `UI-1: game design system`

## Phase UI-2: Board & Gameplay Feel

The core "this is a game" moment.

### Checklist
- [x] Tic-Tac-Toe partial: chunky tiles with soft inner depth, hover lift on
      playable cells, selected-cell glow ring, `pop-in` on newly placed
      symbols (value-keyed marks)
- [x] Connect Four partial: classic blue game frame (gradient
      primary-600→800, rounded, inner shadow), holes with inset depth, discs
      with radial highlight, `disc-drop` animation on newly landed discs,
      column hover/selected glow
- [x] Turn timer: pill pulses and goes red under 5 s (puzzle's 15 s pill in
      UI-4)
- [x] Question card: `shake` on wrong answer (keyed by move count so
      consecutive wrong answers replay it), pop-in "Benar!" on correct,
      chunky answer buttons with hover lift
- [x] `npm run build` + full suite green → commit `UI-2: board & gameplay feel`

## Phase UI-3: Match Stage

Frame every match like an event.

### Checklist
- [x] VS header: avatar initial circles with symbol badges, active player
      `pulse-glow` ring, styled "VS" divider (shared partial)
- [x] Result celebration: win = gradient card + floating trophy + CSS
      confetti pieces; lose/draw variants; reward chips (+XP / +koin) on
      completion mirroring endMatch's winner multiplier
- [x] AI thinking indicator becomes a chat-style bubble next to the AI's
      avatar with bouncing dots
- [x] Applies to Practice and Quick Play match views
- [x] `npm run build` + full suite green → commit `UI-3: match stage`

## Phase UI-4: Lobby, Dashboard & Puzzle Alignment

Make entry points feel like a game menu, not a settings page.

### Checklist
- [x] Dashboard: hero mode cards (Latihan / Quick Play / Puzzle Harian) with
      floating emoji + hover lift; quick links to Peringkat & Prestasi;
      stats HUD (menang/main/streak/koin) with an XP progress bar toward the
      next level
- [x] Quick Play lobby: game picker as two mini board-preview cards (3×3
      grid vs disc grid) with selected glow; chunky "Cari Lawan" button
- [x] Practice picker restyled to match (gradient pill variant)
- [x] Daily Puzzle page aligned: same header/tile/glow styles, chunky
      buttons, pulsing timer, confetti celebration on completion
- [x] `npm run build` + full suite green → commit `UI-4: lobby & dashboard`

## Phase UI-5: Verification Sweep + Closeout

### Checklist
- [x] Full suite green (114 tests / 350 assertions)
- [x] Browser screenshots: dashboard, Practice (both games mid-match), Quick
      Play lobby, Daily Puzzle (completion + confetti), win celebration, and
      a 390 px mobile pass
- [x] Status Log above fully ✅ with commit hashes (UI-5's own hash in
      `git log`)
- [x] Push

---

## Out of Scope
- New features, routes, or component logic changes
- Dark mode
- Custom/remote web fonts
- Sound effects

---

**Last Updated:** July 2026
**Status:** Ready for Implementation
