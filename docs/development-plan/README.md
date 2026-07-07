# 🎮 OtakTangkas Development Plan

> **Transforming Tic-Tac-Toe Educational Platform into a Quiz-Based Multiplayer Gaming Platform**

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Target Market](#target-market)
- [Technology Stack](#technology-stack)
- [MVP Features](#mvp-features)
- [Development Timeline](#development-timeline)
- [Success Metrics](#success-metrics)
- [Documentation Index](#documentation-index)
- [Quick Start Guide](#quick-start-guide)

---

## 🎯 Project Overview

**OtakTangkas.com** is a competitive quiz-based gaming platform targeting the Indonesian market. The name combines "Otak" (Brain) and "Tangkas" (Agile/Smart), meaning "Smart Brain."

### Project Goals

1. **Transform** existing educational tic-tac-toe platform into consumer-focused gaming platform
2. **Engage** Indonesian casual gamers (ages 13-35) with competitive quiz gameplay
3. **Monetize** through ads and premium subscriptions
4. **Build** sustainable, scalable platform for multiple game types
5. **Create** vibrant community with social features and leaderboards

### Core Game Mechanic

Players must **answer quiz questions correctly** to make game moves. This unique mechanic:
- Makes gaming educational without feeling like homework
- Levels the playing field (knowledge > reflexes)
- Encourages repeated play to improve knowledge
- Creates natural difficulty progression

---

## 🇮🇩 Target Market

### Primary Audience
- **Demographics**: Indonesian teens and young adults (13-35 years)
- **Profile**: Casual mobile gamers who enjoy social competition
- **Behavior**: Active on social media, especially WhatsApp and Instagram
- **Device**: Primarily mobile users (80%+ on smartphones)
- **Connectivity**: Often on limited data plans

### Indonesian Market Considerations

#### Language
- **Primary**: Bahasa Indonesia (UI, questions, content)
- **Secondary**: English (optional for advanced users)
- **Code-switching**: Mix of Indonesian and English (common in youth culture)

#### Cultural Factors
- **Community-oriented**: Strong preference for social/multiplayer features
- **Competitive**: Love for rankings, leaderboards, and showing off achievements
- **Sharing culture**: WhatsApp sharing is essential
- **Price-sensitive**: Premium pricing must be affordable (Rp 29,000/month)
- **Mobile-first**: Most gaming happens on smartphones during commutes

#### Popular Question Categories
1. **Math** (basic arithmetic, brain teasers)
2. **Science** (general knowledge, fun facts)
3. **Indonesian Culture** (history, language, traditions)
4. **Pop Culture** (music, movies, celebrities)
5. **General Knowledge** (geography, current events)

---

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 13
- **Language**: PHP 8.4+ (8.3 minimum)
- **Real-time**: Laravel Reverb (first-party, self-hosted WebSocket server)
- **Database**: MySQL 8.4 LTS / PostgreSQL 16+
- **Queue**: Laravel Queue (Redis-backed)
- **Cache**: Redis 7

### Frontend
- **UI Framework**: Livewire 4
- **Styling**: Tailwind CSS 4 (CSS-first configuration)
- **JavaScript**: Alpine.js (bundled with Livewire)
- **Build Tool**: Vite
- **Icons**: Heroicons

### Authentication
- **Scaffolding**: Official Livewire starter kit (replaces Laravel Breeze)

### Admin Panel
- **Framework**: Filament 5
- **Features**: Question management, user management, analytics

### Third-Party Services
- **Ads**: PropellerAds, Adsterra *(post-MVP — Phase 6 deferred)*
- **Analytics**: Google Analytics
- **Error Tracking**: Sentry (optional)
- **CDN**: Cloudflare
- **Email**: AWS SES / Mailgun

### Development Tools
- **Version Control**: Git
- **Testing**: Pest 4 (unit, feature, and Playwright-powered browser tests)
- **Code Style**: Laravel Pint
- **CI/CD**: GitHub Actions

---

## ✨ MVP Features

### Game Modes

#### Solo Modes
1. **Practice Mode**: Play against AI to learn and improve
2. **Daily Puzzle**: Special challenge with global rankings
3. **Endless Mode**: Play until you lose (optional)
4. **Challenge Mode**: Time-limited challenges (optional)

#### Multiplayer Modes
1. **Quick Play**: Automatic matchmaking with similar-level players
2. **Invite Friend**: Private matches with friends
3. **Tournament Mode**: Organized competitions (Phase 2)

### Games (MVP)
1. **Tic-Tac-Toe**: 3x3 grid, answer questions to place symbols
2. **Connect Four (Empat Sejajar)**: 6x7 grid, answer questions to drop
   discs — see [GAME_CONNECT_FOUR.md](./GAME_CONNECT_FOUR.md)
3. **Memory Match (Ingat Pasangan)**: 4x4 card grid, answer questions to
   flip pairs — see [GAME_MEMORY_MATCH.md](./GAME_MEMORY_MATCH.md)

### Progression System
- **XP and Levels**: 1-50 levels
- **Ranks**: Bronze → Silver → Gold → Platinum → Diamond
- **Coins**: Virtual currency for customization
- **Achievements**: 10+ initial badges
- **Streaks**: Daily login, win streaks
- **Leaderboards**: Daily, Weekly, All-Time

### Social Features
- **WhatsApp Sharing**: Share wins, achievements, daily puzzle scores
- **Friend System**: Add friends, see their stats
- **Referral System**: Invite friends for rewards
- **Global Rankings**: Compete with all players

### Question System
- **5 Categories**: Math, Science, Culture, Pop, General
- **Difficulty Levels**: Easy, Medium, Hard
- **Dynamic Selection**: Based on player level
- **Admin Panel**: Easy question management

### Monetization (⏸️ Deferred — post-MVP)

> Monetization (Phase 6) is **not part of the MVP**. The MVP scope ends at Solo Modes (Phase 5) + Launch (Phase 7). The features below are planned for after launch.

- **Ads**: Non-intrusive banner and interstitial ads
- **Premium**: Ad-free experience, exclusive features (Rp 29,000/month)
- **Coins**: Optional in-app purchases

---

## 📅 Development Timeline

**Total Duration**: 5-7 weeks for MVP (monetization deferred to post-launch)

### Week 1: Setup + Foundation
- Environment setup
- Database migrations
- Authentication system
- Guest user system
- Admin panel setup
- Initial seeding

**Deliverable**: Working Laravel app with admin panel

### Week 2: Game Engine
- Game engine architecture
- Tic-Tac-Toe implementation
- AI opponent
- Question service
- Match service
- Real-time game state

**Deliverable**: Playable tic-tac-toe with questions

### Week 3: Frontend
- Dashboard design
- Game board UI
- Question modals
- Responsive design
- Mobile optimization
- Component library

**Deliverable**: Polished UI/UX

### Week 4: Progression
- XP and leveling
- Coins system
- Achievements
- Leaderboards
- Rank system
- User statistics

**Deliverable**: Complete progression loop

### Week 5: Solo Modes
- Practice mode
- AI difficulty levels
- Daily puzzle system
- Puzzle rankings
- Solo rewards

**Deliverable**: All solo modes playable

### ⏸️ Monetization (deferred to post-launch)
- Ad integration, premium subscription, payment integration (Midtrans), revenue tracking
- See [Phase 6: Monetization](PHASE_6_MONETIZATION.md) — implement after the MVP launch

### Week 6-7: Polish + Launch
- Performance optimization
- SEO setup
- Analytics integration
- Testing (all types)
- Deployment
- Marketing materials
- Soft launch

**Deliverable**: Production-ready platform

---

## 📊 Success Metrics

> **Note:** Revenue and premium-subscription targets below apply **post-MVP**, once Phase 6 (Monetization) ships after launch.

### Phase 1 (First Month)
- **Users**: 1,000 registered users
- **DAU**: 200 daily active users
- **Retention**: 30% D1, 15% D7
- **Games Played**: 5,000+ matches
- **Revenue**: Rp 5 million from ads

### Phase 2 (3 Months)
- **Users**: 10,000 registered users
- **DAU**: 2,000 daily active users
- **Retention**: 40% D1, 25% D7
- **Premium Subs**: 100 subscribers
- **Revenue**: Rp 20 million/month

### Phase 3 (6 Months)
- **Users**: 50,000 registered users
- **DAU**: 10,000 daily active users
- **Retention**: 50% D1, 35% D7
- **Premium Subs**: 500 subscribers
- **Revenue**: Rp 50 million/month

### Key Performance Indicators (KPIs)
- **Engagement**: Average 3+ games per session
- **Social**: 20% of users share results
- **Referral**: 15% of new users from referrals
- **Premium Conversion**: 2-5% of users
- **Ad Revenue**: Rp 50-100 per user/month

---

## 📚 Documentation Index

### Development Phases
1. [Phase 0: Setup](PHASE_0_SETUP.md) - Environment and dependencies
2. [Phase 1: Foundation](PHASE_1_FOUNDATION.md) - Database, models, authentication
3. [Phase 2: Game Engine](PHASE_2_GAME_ENGINE.md) - Game logic and AI
4. [Phase 3: Frontend](PHASE_3_FRONTEND.md) - UI/UX components
5. [Phase 4: Progression](PHASE_4_PROGRESSION.md) - XP, achievements, leaderboards
6. [Phase 5: Solo Modes](PHASE_5_SOLO_MODES.md) - Practice and daily puzzles
7. [Phase 6: Monetization](PHASE_6_MONETIZATION.md) - Ads and premium *(⏸️ deferred — post-MVP)*
8. [Phase 7: Launch](PHASE_7_LAUNCH.md) - Deployment and marketing

### Technical Documentation
- [Database Schema](DATABASE_SCHEMA.md) - Complete database design
- [Architecture](ARCHITECTURE.md) - System architecture overview
- [API Endpoints](API_ENDPOINTS.md) - API and Livewire endpoints
- [Testing Guide](TESTING_GUIDE.md) - Testing strategies and examples

### Related Documentation (Existing)
- [Current Architecture](../ARCHITECTURE.md)
- [Current Database](../DATABASE.md)
- [Current Game Logic](../GAME_LOGIC.md)
- [Current Testing](../TESTING.md)

---

## 🚀 Quick Start Guide

### For Developers

1. **Read Documentation**
   - Start with [Phase 0: Setup](PHASE_0_SETUP.md)
   - Review [Architecture](ARCHITECTURE.md)
   - Check [Database Schema](DATABASE_SCHEMA.md)

2. **Environment Setup**
   ```bash
   # Create a fresh Laravel 13 app with the Livewire starter kit
   laravel new otaktangkas --using=laravel/livewire-starter-kit
   cd otaktangkas
   
   # Install additional dependencies
   composer require filament/filament spatie/laravel-permission
   php artisan install:broadcasting   # installs Reverb + Echo
   npm install
   
   # Setup environment
   cp .env.example .env
   php artisan key:generate
   
   # Configure database and Reverb in .env
   
   # Run migrations and seeds
   php artisan migrate --seed
   
   # Build assets
   npm run dev
   
   # Start servers
   php artisan serve
   php artisan reverb:start
   ```

3. **Follow Phases**
   - Implement features phase by phase
   - Test each phase thoroughly before moving forward
   - Reference code examples in documentation

### For Project Managers

1. **Review Timeline**: Check development timeline and milestones
2. **Track Progress**: Use phase checklists for progress tracking
3. **Monitor Metrics**: Set up success metrics tracking
4. **Plan Marketing**: Prepare for launch with marketing materials

### For AI Coding Agents

This documentation is optimized for AI implementation:
- Clear acceptance criteria for each feature
- Production-ready code examples
- Step-by-step implementation guides
- Complete database schemas
- Architectural patterns

Start with Phase 0 and work sequentially through each phase.

---

## 🔄 Migration from Current System

### What's Changing
- **Focus**: Educational tool → Consumer gaming platform
- **Language**: English → Bahasa Indonesia
- **Audience**: Students/Teachers → Casual gamers
- **Monetization**: None → Ads + Premium *(post-MVP — Phase 6 deferred)*
- **Features**: Tournaments → Quick play + Daily puzzles
- **Stack**: Upgraded to Laravel 13 + Livewire 4 + Tailwind CSS 4
- **Real-time**: Pusher → Laravel Reverb (self-hosted, same protocol)

### What's Staying
- Laravel + Livewire architecture
- Real-time gameplay via WebSockets (Laravel Echo)
- Question-based gameplay mechanic
- Tic-Tac-Toe as core game
- User authentication system

### Migration Strategy
1. **Phase 0-1**: Set up new foundation alongside existing code
2. **Phase 2-3**: Build new game engine and UI
3. **Phase 4-5**: Add progression and solo modes
4. **Phase 7**: Deploy as separate platform or replace existing
5. **Phase 6 (post-MVP)**: Integrate monetization after launch

---

## 📞 Support & Contact

### Development Team
- **Technical Lead**: [Name/Contact]
- **Backend Developer**: [Name/Contact]
- **Frontend Developer**: [Name/Contact]
- **Product Manager**: [Name/Contact]

### Resources
- **GitHub Repository**: [Link]
- **Project Board**: [Link]
- **Slack/Discord**: [Link]
- **Documentation**: [Link]

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](../../LICENSE) file for details.

---

## 🎉 Let's Build OtakTangkas!

Ready to start? Head to [Phase 0: Setup](PHASE_0_SETUP.md) to begin your development journey!

**Target Launch**: 7 weeks from project start
**First Milestone**: MVP ready for beta testing in 5 weeks
**Go Live**: Week 7 with initial marketing push

---

*Last Updated: July 2026*
*Version: 2.0.0*
