# Phase 7: Launch Preparation & Deployment

## Overview
Complete launch guide for OtakTangkas including pre-launch checklist, testing procedures, deployment steps, performance optimization, SEO setup, and post-launch monitoring. Optimized for the Indonesian market.

## Table of Contents
- [Pre-Launch Checklist](#pre-launch-checklist)
- [Testing Procedures](#testing-procedures)
- [Deployment Guide](#deployment-guide)
- [Performance Optimization](#performance-optimization)
- [SEO Setup](#seo-setup)
- [Social Media Integration](#social-media-integration)
- [Analytics Setup](#analytics-setup)
- [Error Tracking](#error-tracking)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Marketing Strategy](#marketing-strategy)
- [Launch Timeline](#launch-timeline)
- [Post-Launch Metrics](#post-launch-metrics)

---

## Pre-Launch Checklist

### Core Functionality
- [ ] User authentication (login, register, forgot password)
- [ ] Matchmaking system working
- [ ] Game logic fully functional
- [ ] Question system with all categories
- [ ] XP and leveling system
- [ ] Coin economy
- [ ] Achievement system
- [ ] Leaderboards (daily, weekly, all-time)
- [ ] Practice mode with AI
- [ ] Daily puzzle system
- [ ] Profile and settings

### Content
- [ ] Minimum 500 questions across all categories
- [ ] Questions reviewed for accuracy
- [ ] Questions appropriate for Indonesian audience
- [ ] No offensive or controversial content
- [ ] All text translated to Bahasa Indonesia
- [ ] Terms of Service in Bahasa
- [ ] Privacy Policy in Bahasa

### UI/UX
- [ ] All pages responsive (mobile, tablet, desktop)
- [ ] Images optimized (WebP format)
- [ ] Loading states for all async operations
- [ ] Error messages user-friendly
- [ ] Success notifications working
- [ ] Navigation intuitive
- [ ] Colors and branding consistent
- [ ] Icons and emojis display correctly

### Performance
- [ ] Page load time < 3 seconds (3G connection)
- [ ] Database queries optimized
- [ ] Caching implemented (Redis/Memcached)
- [ ] Assets minified and compressed
- [ ] CDN configured for static assets
- [ ] Database indexed properly
- [ ] Queue workers running for background jobs

### Security
- [ ] HTTPS enabled with valid SSL certificate
- [ ] CSRF protection enabled
- [ ] XSS protection enabled
- [ ] SQL injection prevention (parameterized queries)
- [ ] Rate limiting on API endpoints
- [ ] User input sanitization
- [ ] Secure password hashing (bcrypt)
- [ ] Session management secure
- [ ] Environment variables protected
- [ ] Database backups automated

### Monetization
- [ ] Ad networks configured and tested
- [ ] Ad placements non-intrusive
- [ ] Premium subscription working
- [ ] Payment gateway integrated
- [ ] Revenue tracking implemented

### Legal & Compliance
- [ ] Terms of Service published
- [ ] Privacy Policy published
- [ ] Cookie consent (if using cookies)
- [ ] GDPR compliance (if applicable)
- [ ] Age restriction notice (if required)
- [ ] Contact information available

### Infrastructure
- [ ] Production server configured
- [ ] Database backup system
- [ ] Log rotation configured
- [ ] Monitoring tools installed
- [ ] Uptime monitoring active
- [ ] Email service configured
- [ ] Domain and DNS configured

---

## Testing Procedures

### Unit Testing

```bash
# Run all unit tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

#### Key Areas to Test

```php
<?php
// tests/Unit/XPServiceTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\XPService;

class XPServiceTest extends TestCase
{
    protected XPService $xpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xpService = new XPService();
    }

    public function test_xp_calculation_for_level()
    {
        $this->assertEquals(100, $this->xpService->getXPForLevel(1));
        $this->assertEquals(282, $this->xpService->getXPForLevel(2));
        $this->assertEquals(520, $this->xpService->getXPForLevel(3));
    }

    public function test_level_calculation_from_xp()
    {
        $this->assertEquals(1, $this->xpService->calculateLevel(0));
        $this->assertEquals(1, $this->xpService->calculateLevel(99));
        $this->assertEquals(2, $this->xpService->calculateLevel(100));
        $this->assertEquals(3, $this->xpService->calculateLevel(300));
    }

    public function test_xp_progress_percentage()
    {
        $progress = $this->xpService->getXPProgress(150, 2);
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThan(100, $progress);
    }
}
```

### Integration Testing

```php
<?php
// tests/Feature/GameFlowTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_quick_play()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/game/quick-play/start');

        $response->assertStatus(200);
        $this->assertDatabaseHas('games', [
            'player1_id' => $user->id,
            'status' => 'waiting',
        ]);
    }

    public function test_matchmaking_pairs_two_players()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 starts searching
        $this->actingAs($user1)->post('/game/quick-play/start');

        // User 2 starts searching
        $this->actingAs($user2)->post('/game/quick-play/start');

        // Should create a game with both players
        $this->assertDatabaseHas('games', [
            'player1_id' => $user1->id,
            'player2_id' => $user2->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_game_awards_xp_to_winner()
    {
        $game = Game::factory()->create([
            'status' => 'completed',
            'winner_id' => 1,
        ]);

        $winner = User::find($game->winner_id);
        $initialXP = $winner->xp;

        // Trigger reward processing
        app(\App\Services\ProgressionService::class)
            ->processGameCompletion($game, $winner);

        $winner->refresh();
        $this->assertGreaterThan($initialXP, $winner->xp);
    }
}
```

### Browser Testing (Laravel Dusk)

```bash
# Install Dusk
composer require --dev laravel/dusk
php artisan dusk:install

# Run Dusk tests
php artisan dusk
```

```php
<?php
// tests/Browser/GamePlayTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class GamePlayTest extends DuskTestCase
{
    public function test_user_can_play_practice_game()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/practice')
                ->select('difficulty', 'medium')
                ->press('Mulai Main')
                ->waitForText('Giliran Anda')
                ->assertSee('Practice Mode')
                ->click('.game-cell-0')
                ->waitFor('.question-modal')
                ->assertSee('Jawab Pertanyaan')
                ->click('.answer-option-0')
                ->pause(1000)
                ->assertDontSee('.question-modal');
        });
    }
}
```

### Performance Testing

```bash
# Install Apache Bench (comes with Apache)
# Test homepage
ab -n 1000 -c 10 https://otaktangkas.com/

# Test API endpoint
ab -n 500 -c 5 -H "Authorization: Bearer TOKEN" https://otaktangkas.com/api/user
```

### Load Testing with Locust

```python
# locustfile.py
from locust import HttpUser, task, between

class GameUser(HttpUser):
    wait_time = between(1, 3)

    def on_start(self):
        # Login
        self.client.post("/login", {
            "email": "test@example.com",
            "password": "password"
        })

    @task(3)
    def view_dashboard(self):
        self.client.get("/dashboard")

    @task(2)
    def start_quick_play(self):
        self.client.post("/game/quick-play/start")

    @task(1)
    def view_leaderboard(self):
        self.client.get("/leaderboard")
```

```bash
# Run load test
locust -f locustfile.py --host=https://otaktangkas.com
```

### Mobile Testing Checklist
- [ ] Test on Android (Chrome, Samsung Internet)
- [ ] Test on iOS (Safari)
- [ ] Test on different screen sizes (320px, 375px, 414px)
- [ ] Test touch interactions
- [ ] Test landscape orientation
- [ ] Test slow 3G connection
- [ ] Test offline behavior
- [ ] Test PWA installation

---

## Deployment Guide

### Server Requirements

```
- PHP 8.1 or higher
- MySQL 8.0 or MariaDB 10.3+
- Redis 6.0+
- Node.js 18+
- Composer 2.x
- Nginx or Apache
- SSL Certificate
- Minimum 2GB RAM
- 20GB SSD storage
```

### Deployment Steps

#### 1. Server Setup (Ubuntu 22.04)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-redis \
    php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install -y nginx
sudo systemctl enable nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-nginx
```

#### 2. Clone and Configure Application

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/otaktangkas.git
cd otaktangkas

# Set permissions
sudo chown -R www-data:www-data /var/www/otaktangkas
sudo chmod -R 755 /var/www/otaktangkas
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Environment configuration
cp .env.example .env
nano .env  # Edit with production values

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3. Nginx Configuration

```nginx
# /etc/nginx/sites-available/otaktangkas

server {
    listen 80;
    listen [::]:80;
    server_name otaktangkas.com www.otaktangkas.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name otaktangkas.com www.otaktangkas.com;
    
    root /var/www/otaktangkas/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/otaktangkas.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/otaktangkas.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript 
               application/json application/javascript application/xml+rss;

    # Logging
    access_log /var/log/nginx/otaktangkas-access.log;
    error_log /var/log/nginx/otaktangkas-error.log;

    # Laravel Configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static assets caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/otaktangkas /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Get SSL certificate
sudo certbot --nginx -d otaktangkas.com -d www.otaktangkas.com
```

#### 4. Queue Workers Setup

```bash
# Create supervisor config
sudo nano /etc/supervisor/conf.d/otaktangkas-worker.conf
```

```ini
[program:otaktangkas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/otaktangkas/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/otaktangkas/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start otaktangkas-worker:*
```

#### 5. Scheduled Tasks

```bash
# Add to crontab
sudo crontab -e -u www-data
```

```cron
* * * * * cd /var/www/otaktangkas && php artisan schedule:run >> /dev/null 2>&1
```

#### 6. Database Optimization

```sql
-- Create production database user
CREATE USER 'otaktangkas'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON otaktangkas.* TO 'otaktangkas'@'localhost';
FLUSH PRIVILEGES;

-- Performance tuning in /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 0
query_cache_type = 0
max_connections = 200
```

---

## Performance Optimization

### Database Optimization

```php
<?php
// Eager loading to prevent N+1 queries
$games = Game::with(['player1', 'player2', 'winner'])->get();

// Use select to limit columns
$users = User::select('id', 'name', 'avatar', 'level')->get();

// Use chunks for large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process users
    }
});

// Index frequently queried columns
Schema::table('games', function (Blueprint $table) {
    $table->index(['status', 'created_at']);
    $table->index(['player1_id', 'player2_id']);
});
```

### Caching Strategy

```php
<?php
// Cache leaderboard data
Cache::remember('leaderboard:daily', 300, function () {
    return User::orderBy('daily_xp', 'desc')->take(50)->get();
});

// Cache user stats
Cache::remember("user:stats:{$userId}", 600, function () use ($userId) {
    return User::find($userId)->getStatsAttribute();
});

// Clear cache on updates
Cache::forget('leaderboard:daily');
Cache::tags(['user', $userId])->flush();
```

### Redis Configuration

```bash
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
```

```php
<?php
// config/database.php
'redis' => [
    'client' => 'phpredis',
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'otaktangkas:'),
    ],
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
];
```

### Asset Optimization

```bash
# Optimize images with imageoptim or tinypng
npm install -g imageoptim-cli
imageoptim --quality=85 public/images/**/*

# Use WebP format
cwebp -q 85 image.jpg -o image.webp

# Minify CSS and JS (automatic with Vite)
npm run build
```

### CDN Setup (Cloudflare)

1. Sign up for Cloudflare
2. Add your domain
3. Update nameservers
4. Enable:
   - Auto Minify (CSS, JS, HTML)
   - Brotli compression
   - HTTP/2
   - Cache Level: Standard
   - Browser Cache TTL: 4 hours

---

## SEO Setup

### Meta Tags

```blade
<!-- resources/views/layouts/app.blade.php -->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', 'OtakTangkas - Game Trivia Tic Tac Toe Indonesia')</title>
    <meta name="description" content="@yield('description', 'Main game trivia seru dengan format Tic Tac Toe! Asah otak, jawab pertanyaan, dan menangkan hadiah. Game trivia Indonesia terbaik.')">
    <meta name="keywords" content="game trivia, tic tac toe, quiz indonesia, game edukasi, trivia online, otaktangkas">
    <meta name="author" content="OtakTangkas">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', 'OtakTangkas - Game Trivia Tic Tac Toe')">
    <meta property="og:description" content="@yield('og_description', 'Main game trivia seru dengan format Tic Tac Toe!')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'OtakTangkas')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Game Trivia Tic Tac Toe Indonesia')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/og-image.jpg'))">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <!-- Manifest for PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0ea5e9">
</head>
```

### robots.txt

```
# public/robots.txt
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /login
Disallow: /register

Sitemap: https://otaktangkas.com/sitemap.xml
```

### Sitemap Generation

```php
<?php
// routes/web.php
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
```

```php
<?php
// app/Http/Controllers/SitemapController.php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Homepage
        $sitemap .= $this->addUrl('/', 1.0, 'daily');
        
        // Static pages
        $sitemap .= $this->addUrl('/about', 0.8, 'weekly');
        $sitemap .= $this->addUrl('/leaderboard', 0.9, 'daily');
        
        $sitemap .= '</urlset>';

        return response($sitemap, 200)->header('Content-Type', 'application/xml');
    }

    protected function addUrl($url, $priority, $changefreq)
    {
        return sprintf(
            '<url><loc>%s</loc><priority>%.1f</priority><changefreq>%s</changefreq></url>',
            url($url),
            $priority,
            $changefreq
        );
    }
}
```

### Structured Data (JSON-LD)

```blade
<!-- Add to homepage -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "OtakTangkas",
  "description": "Game trivia Tic Tac Toe Indonesia",
  "url": "https://otaktangkas.com",
  "applicationCategory": "GameApplication",
  "operatingSystem": "Web, Android, iOS",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "IDR"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "1250"
  }
}
</script>
```

---

## Social Media Integration

### Share Functionality

```php
<?php
// app/Services/SocialShareService.php

namespace App\Services;

class SocialShareService
{
    public function generateShareLinks(string $text, string $url): array
    {
        return [
            'whatsapp' => $this->getWhatsAppLink($text, $url),
            'facebook' => $this->getFacebookLink($url),
            'twitter' => $this->getTwitterLink($text, $url),
            'telegram' => $this->getTelegramLink($text, $url),
        ];
    }

    protected function getWhatsAppLink(string $text, string $url): string
    {
        return 'https://wa.me/?text=' . urlencode($text . ' ' . $url);
    }

    protected function getFacebookLink(string $url): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url);
    }

    protected function getTwitterLink(string $text, string $url): string
    {
        return 'https://twitter.com/intent/tweet?text=' . urlencode($text) . '&url=' . urlencode($url);
    }

    protected function getTelegramLink(string $text, string $url): string
    {
        return 'https://t.me/share/url?url=' . urlencode($url) . '&text=' . urlencode($text);
    }
}
```

### Share Component

```blade
<!-- resources/views/components/share-buttons.blade.php -->
@props(['text', 'url'])

@php
    $shareService = app(\App\Services\SocialShareService::class);
    $links = $shareService->generateShareLinks($text, $url);
@endphp

<div class="flex items-center space-x-3">
    <span class="text-sm text-gray-600 font-semibold">Bagikan:</span>
    
    <!-- WhatsApp -->
    <a href="{{ $links['whatsapp'] }}" 
       target="_blank"
       class="w-10 h-10 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors">
        <i class="fab fa-whatsapp text-xl"></i>
    </a>
    
    <!-- Facebook -->
    <a href="{{ $links['facebook'] }}" 
       target="_blank"
       class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center transition-colors">
        <i class="fab fa-facebook-f"></i>
    </a>
    
    <!-- Twitter -->
    <a href="{{ $links['twitter'] }}" 
       target="_blank"
       class="w-10 h-10 bg-sky-500 hover:bg-sky-600 text-white rounded-full flex items-center justify-center transition-colors">
        <i class="fab fa-twitter"></i>
    </a>
    
    <!-- Telegram -->
    <a href="{{ $links['telegram'] }}" 
       target="_blank"
       class="w-10 h-10 bg-blue-500 hover:bg-blue-600 text-white rounded-full flex items-center justify-center transition-colors">
        <i class="fab fa-telegram-plane"></i>
    </a>
</div>
```

---

## Analytics Setup

### Google Analytics 4

```blade
<!-- Add to layouts/app.blade.php before </head> -->
@production
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>
@endproduction
```

### Event Tracking

```javascript
// resources/js/analytics.js

export function trackEvent(eventName, eventParams = {}) {
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, eventParams);
    }
}

// Usage examples
trackEvent('game_started', {
    game_type: 'quick_play',
    user_level: 5
});

trackEvent('game_completed', {
    game_type: 'practice',
    result: 'win',
    duration: 120
});

trackEvent('purchase', {
    transaction_id: 'PREMIUM-123',
    value: 29000,
    currency: 'IDR'
});
```

### Custom Dashboard

```php
<?php
// app/Http/Controllers/Admin/AnalyticsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Game;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $lastWeek = Carbon::now()->subWeek();
        $lastMonth = Carbon::now()->subMonth();

        $metrics = [
            'total_users' => User::count(),
            'active_users_today' => User::where('last_login_date', $today)->count(),
            'new_users_week' => User::where('created_at', '>=', $lastWeek)->count(),
            'total_games' => Game::count(),
            'games_today' => Game::whereDate('created_at', $today)->count(),
            'avg_session_duration' => $this->getAverageSessionDuration(),
            'retention_rate' => $this->getRetentionRate(),
        ];

        return view('admin.analytics', compact('metrics'));
    }

    protected function getAverageSessionDuration()
    {
        // Implement session tracking logic
        return 0;
    }

    protected function getRetentionRate()
    {
        $totalUsers = User::count();
        $activeLastWeek = User::where('last_login_date', '>=', Carbon::now()->subWeek())->count();
        
        return $totalUsers > 0 ? round(($activeLastWeek / $totalUsers) * 100, 2) : 0;
    }
}
```

---

## Error Tracking

### Sentry Setup

```bash
composer require sentry/sentry-laravel
```

```php
<?php
// config/sentry.php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
    'environment' => env('APP_ENV', 'production'),
];
```

```env
# .env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2
```

### Error Logging

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        // Custom error pages
        if ($this->isHttpException($exception)) {
            $statusCode = $exception->getStatusCode();
            
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [], $statusCode);
            }
        }

        return parent::render($request, $exception);
    }
}
```

---

## Monitoring and Maintenance

### Uptime Monitoring

**Recommended Services:**
- UptimeRobot (Free tier available)
- Pingdom
- StatusCake

**Setup Example (UptimeRobot):**
1. Create account at uptimerobot.com
2. Add new monitor:
   - Type: HTTP(s)
   - URL: https://otaktangkas.com
   - Interval: 5 minutes
   - Alert when down for: 2 minutes

### Server Monitoring

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor disk usage
df -h

# Monitor memory
free -h

# Monitor processes
htop

# Monitor logs
tail -f /var/log/nginx/otaktangkas-error.log
tail -f /var/www/otaktangkas/storage/logs/laravel.log
```

### Automated Backups

```bash
#!/bin/bash
# /usr/local/bin/backup-otaktangkas.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/otaktangkas"
DB_NAME="otaktangkas"
DB_USER="otaktangkas"
DB_PASS="password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/otaktangkas/storage/app/public

# Delete backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-otaktangkas.sh

# Add to crontab (daily at 2 AM)
0 2 * * * /usr/local/bin/backup-otaktangkas.sh >> /var/log/backup.log 2>&1
```

### Health Check Endpoint

```php
<?php
// routes/web.php
Route::get('/health', function () {
    try {
        // Check database
        DB::connection()->getPdo();
        
        // Check Redis
        Redis::ping();
        
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => 'ok',
                'redis' => 'ok',
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});
```

---

## Marketing Strategy

### Pre-Launch (2 weeks before)

- [ ] Create social media accounts (Instagram, Facebook, Twitter)
- [ ] Design promotional materials (banners, posters)
- [ ] Prepare launch announcement content
- [ ] Set up landing page with email signup
- [ ] Reach out to Indonesian gaming communities
- [ ] Create teaser videos/GIFs
- [ ] Contact Indonesian tech bloggers/influencers

### Launch Day

- [ ] Post launch announcement on all platforms
- [ ] Share on Indonesian Facebook groups
- [ ] Post on Indonesian Reddit (r/indonesia)
- [ ] Submit to Indonesian app directories
- [ ] Send email to pre-registered users
- [ ] Run initial paid ads (Google Ads, Facebook Ads)
- [ ] Monitor for issues and user feedback

### Post-Launch (First month)

- [ ] Daily social media posts
- [ ] Respond to all user feedback
- [ ] Run contests/giveaways
- [ ] Partner with Indonesian streamers
- [ ] Submit to game review sites
- [ ] Optimize based on analytics
- [ ] Iterate based on user feedback

### Content Calendar

**Week 1-2: Awareness**
- Daily tips and tricks posts
- User testimonials (if any)
- Game feature highlights
- "Did you know?" facts

**Week 3-4: Engagement**
- Weekly challenges
- Leaderboard highlights
- Community spotlights
- Behind-the-scenes content

### Advertising Budget (Monthly)

```
Facebook Ads: Rp 1,000,000 ($65)
- Target: Ages 18-35, Indonesia
- Interest: Trivia, Quiz games, Education

Google Ads: Rp 1,000,000 ($65)
- Keywords: game trivia, quiz indonesia, tic tac toe online
- Display Network: Gaming websites

Instagram Ads: Rp 500,000 ($33)
- Stories and Feed ads
- Target: Engaged audience

Total: Rp 2,500,000/month ($165/month)
```

---

## Launch Timeline

### Week -4 (1 Month Before Launch)
- [ ] Complete all core features
- [ ] Add minimum 500 questions
- [ ] Complete all testing
- [ ] Set up production server
- [ ] Configure domain and SSL

### Week -3
- [ ] Deploy to production
- [ ] Closed beta testing (friends/family)
- [ ] Fix critical bugs
- [ ] Optimize performance
- [ ] Set up monitoring and analytics

### Week -2
- [ ] Create marketing materials
- [ ] Set up social media accounts
- [ ] Start pre-launch marketing
- [ ] Prepare press release
- [ ] Contact influencers

### Week -1
- [ ] Final testing round
- [ ] Pre-register early users
- [ ] Schedule launch posts
- [ ] Prepare customer support
- [ ] Final server checks

### Launch Day
- [ ] 00:00 - Go live
- [ ] 09:00 - Post announcements
- [ ] 12:00 - Monitor metrics
- [ ] 18:00 - Address any issues
- [ ] 21:00 - Daily summary report

### Week +1
- [ ] Daily monitoring
- [ ] Respond to feedback
- [ ] Quick bug fixes
- [ ] Adjust marketing based on metrics
- [ ] Plan first update

---

## Post-Launch Metrics

### Key Performance Indicators (KPIs)

**User Acquisition:**
- Daily Active Users (DAU)
- Monthly Active Users (MAU)
- New user registrations
- User growth rate

**Engagement:**
- Average session duration
- Games played per user
- Daily puzzle completion rate
- Return rate (7-day, 30-day)

**Monetization:**
- Ad impressions
- Ad click-through rate (CTR)
- Premium conversion rate
- Average Revenue Per User (ARPU)

**Technical:**
- Page load time
- Error rate
- Uptime percentage
- API response time

### Success Metrics (First 3 Months)

```
Month 1 Goals:
- 1,000 registered users
- 500 DAU
- 70% 7-day retention
- 10,000+ games played
- 99% uptime

Month 2 Goals:
- 3,000 registered users
- 1,500 DAU
- 60% 7-day retention
- 30,000+ games played
- 20+ premium subscribers

Month 3 Goals:
- 10,000 registered users
- 4,000 DAU
- 55% 7-day retention
- 100,000+ games played
- 100+ premium subscribers
- Break even on hosting costs
```

### Monthly Review Checklist

- [ ] Review all KPI metrics
- [ ] Analyze user feedback
- [ ] Review server performance
- [ ] Check revenue vs costs
- [ ] Plan feature updates
- [ ] Adjust marketing strategy
- [ ] Update content (questions)
- [ ] Review security logs

---

## Completion Checklist

### Pre-Launch
- [ ] All features completed and tested
- [ ] 500+ questions added
- [ ] All tests passing
- [ ] Production server configured
- [ ] Domain and SSL set up
- [ ] Monitoring configured
- [ ] Backups automated
- [ ] Marketing materials ready

### Launch
- [ ] Application deployed
- [ ] DNS propagated
- [ ] SSL certificate valid
- [ ] Analytics tracking
- [ ] Error tracking active
- [ ] Social media announced
- [ ] Monitoring alerts configured

### Post-Launch
- [ ] Daily monitoring active
- [ ] User feedback collected
- [ ] Metrics tracked
- [ ] Quick response to issues
- [ ] Regular updates planned
- [ ] Community management

---

## Troubleshooting Guide

### Common Issues

**Issue: Site is slow**
```bash
# Check server resources
htop
df -h

# Check database
mysql -u root -p -e "SHOW FULL PROCESSLIST;"

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

**Issue: Queue not processing**
```bash
# Check queue workers
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart otaktangkas-worker:*

# Check failed jobs
php artisan queue:failed
```

**Issue: High database load**
```sql
-- Check slow queries
SHOW FULL PROCESSLIST;

-- Check table sizes
SELECT 
    table_name AS `Table`,
    round(((data_length + index_length) / 1024 / 1024), 2) `Size in MB`
FROM information_schema.TABLES
WHERE table_schema = "otaktangkas"
ORDER BY (data_length + index_length) DESC;
```

---

## Related Documentation
- [PHASE_1_FOUNDATION.md](./PHASE_1_FOUNDATION.md)
- [PHASE_2_CORE.md](./PHASE_2_CORE.md)
- [PHASE_3_FRONTEND.md](./PHASE_3_FRONTEND.md)
- [PHASE_4_PROGRESSION.md](./PHASE_4_PROGRESSION.md)
- [PHASE_5_SOLO_MODES.md](./PHASE_5_SOLO_MODES.md)
- [PHASE_6_MONETIZATION.md](./PHASE_6_MONETIZATION.md)

---

**Last Updated:** 2024
**Status:** Ready for Implementation
**Priority:** HIGH - Complete before launch
