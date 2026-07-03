# DATABASE SCHEMA DOCUMENTATION

**OtakTangkas Platform - Complete Database Schema**  
**Version:** 1.0  
**Last Updated:** 2025  
**Database Engine:** MySQL 8.0+ / MariaDB 10.6+  
**Charset:** utf8mb4 (Unicode support for Indonesian characters)  
**Collation:** utf8mb4_unicode_ci

---

## Table of Contents

1. [Overview](#overview)
2. [Entity Relationship Diagram](#entity-relationship-diagram)
3. [Core Tables](#core-tables)
4. [Game Tables](#game-tables)
5. [Tournament Tables](#tournament-tables)
6. [Content Management Tables](#content-management-tables)
7. [System Tables](#system-tables)
8. [Foreign Key Relationships](#foreign-key-relationships)
9. [Index Strategy](#index-strategy)
10. [Migration Order](#migration-order)
11. [Sample Data Structures](#sample-data-structures)
12. [Database Optimization](#database-optimization)
13. [Backup & Restore](#backup--restore)

---

## Overview

The OtakTangkas platform uses a relational database structure designed to support:
- **Educational gaming** with question-answer mechanics
- **Tournament system** with group-based competitions
- **School-based user management** for Indonesian education
- **Real-time multiplayer** tic-tac-toe gameplay
- **Content management** for questions, categories, and tutorials

**Key Design Principles:**
- Normalized to 3NF (Third Normal Form)
- Soft deletes for critical data (via Laravel timestamps)
- Cascade deletes for dependent records
- Indexed foreign keys for performance
- JSON columns for flexible metadata
- UTF-8 support for Bahasa Indonesia

---

## Entity Relationship Diagram

```
┌─────────────────┐         ┌──────────────────┐
│     schools     │         │   categories     │
│─────────────────│         │──────────────────│
│ id (PK)         │         │ id (PK)          │
│ name            │         │ name             │
│ address         │         │ user_id (FK)     │
│ phone           │         │ created_at       │
│ created_at      │         │ updated_at       │
│ updated_at      │         └──────────────────┘
└─────────────────┘                   │
         │                             │
         │                             │ category_id
         │ school_id                   │
         │                             ▼
┌────────▼────────────────────────────────────────┐
│                    users                        │
│─────────────────────────────────────────────────│
│ id (PK)                                         │
│ name                                            │
│ email (UNIQUE)                                  │
│ nomor_induk (UNIQUE) - Student/Teacher ID       │
│ school_id (FK) ──────────────┐                 │
│ password                      │                 │
│ profile_photo_path            │                 │
│ email_verified_at             │                 │
│ remember_token                │                 │
│ created_at                    │                 │
│ updated_at                    │                 │
└───────────────────────────────┼─────────────────┘
                                │
                 ┌──────────────┴─────────────┐
                 │                            │
         user_id │                            │ user_id
                 ▼                            ▼
    ┌──────────────────────┐     ┌──────────────────────┐
    │    tournaments       │     │     questions        │
    │──────────────────────│     │──────────────────────│
    │ id (PK)              │     │ id (PK)              │
    │ user_id (FK)         │     │ text                 │
    │ name                 │     │ category_id (FK)     │
    │ category_id (FK)     │     │ created_at           │
    │ status (enum)        │     │ updated_at           │
    │ created_at           │     └──────────────────────┘
    │ updated_at           │                │
    └──────────────────────┘                │ question_id
                │                            ▼
                │                 ┌──────────────────────┐
                │ tournament_id   │      answers         │
                │                 │──────────────────────│
                ▼                 │ id (PK)              │
    ┌──────────────────────┐     │ question_id (FK)     │
    │       groups         │     │ answer_text          │
    │──────────────────────│     │ is_correct (bool)    │
    │ id (PK)              │     │ created_at           │
    │ tournament_id (FK)   │     │ updated_at           │
    │ name                 │     └──────────────────────┘
    │ created_at           │
    │ updated_at           │
    └──────────────────────┘
                │
                │ group_id
                │
    ┌───────────┴──────────────┐
    │                          │
    ▼                          ▼
┌──────────────────┐  ┌──────────────────────────┐
│ group_members    │  │       matches            │
│──────────────────│  │──────────────────────────│
│ id (PK)          │  │ id (PK)                  │
│ group_id (FK)    │  │ tournament_id (FK)       │
│ user_id (FK)     │  │ group1_id (FK)           │
│ created_at       │  │ group2_id (FK)           │
│ updated_at       │  │ winner_id (FK)           │
└──────────────────┘  │ round                    │
                      │ is_draw (bool)           │
                      │ turn_number              │
                      │ current_turn_group_id    │
                      │ tiebreaker_started_at    │
                      │ tiebreaker_group1_score  │
                      │ tiebreaker_group2_score  │
                      │ created_at               │
                      │ updated_at               │
                      └──────────────────────────┘
                                  │
                      ┌───────────┴──────────────┐
                      │                          │
                      ▼                          ▼
        ┌──────────────────────┐   ┌──────────────────────┐
        │    user_moves        │   │    user_joins        │
        │──────────────────────│   │──────────────────────│
        │ id (PK)              │   │ id (PK)              │
        │ user_id (FK)         │   │ user_id (FK)         │
        │ matches_id (FK)      │   │ matches_id (FK)      │
        │ group_id (FK)        │   │ created_at           │
        │ symbol (enum: X,O)   │   │ updated_at           │
        │ position (0-8)       │   └──────────────────────┘
        │ correct (bool)       │
        │ created_at           │
        │ updated_at           │
        └──────────────────────┘

┌──────────────────────┐
│   match_helps        │
│──────────────────────│
│ id (PK)              │
│ matches_id (FK)      │
│ group_id (FK)        │
│ used_at              │
│ created_at           │
│ updated_at           │
└──────────────────────┘

┌──────────────────────┐         ┌──────────────────────┐
│   cara_bermains      │         │      abouts          │
│──────────────────────│         │──────────────────────│
│ id (PK)              │         │ id (PK)              │
│ title                │         │ title                │
│ content (TEXT)       │         │ content (TEXT)       │
│ created_at           │         │ created_at           │
│ updated_at           │         │ updated_at           │
└──────────────────────┘         └──────────────────────┘

┌──────────────────────┐
│    rich_texts        │
│──────────────────────│
│ id (PK)              │
│ field                │
│ body (LONGTEXT)      │
│ richable_type        │
│ richable_id          │
│ created_at           │
│ updated_at           │
└──────────────────────┘
```

---

## Core Tables

### 1. users

**Purpose:** Central user management for students, teachers, and administrators.

```sql
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE DEFAULT NULL,
  `nomor_induk` VARCHAR(255) UNIQUE DEFAULT NULL COMMENT 'NIS/NIK - Student or Teacher ID',
  `school_id` BIGINT UNSIGNED DEFAULT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `profile_photo_path` VARCHAR(2048) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `users_school_id_index` (`school_id`),
  INDEX `users_email_index` (`email`),
  INDEX `users_nomor_induk_index` (`nomor_induk`),
  
  CONSTRAINT `users_school_id_foreign` 
    FOREIGN KEY (`school_id`) 
    REFERENCES `schools` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `id`: Primary key, auto-increment
- `name`: Full name (supports Indonesian names with multiple words)
- `email`: Optional for student accounts (teachers require email)
- `nomor_induk`: Unique identifier for Indonesian students (NIS) or teachers (NIK)
- `school_id`: Links to school (NULL for system admins)
- `profile_photo_path`: Stores URL/path to profile picture
- `email_verified_at`: Email verification timestamp (Laravel Breeze)
- `remember_token`: For "Remember Me" functionality

**Indonesian Context:**
- Supports `nomor_induk` as alternative to email login
- Profile can use Indonesian characters (UTF-8)
- School association enables school-based leaderboards

---

### 2. schools

**Purpose:** Manages school information for the Indonesian education system.

```sql
CREATE TABLE `schools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `schools_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `name`: School name (e.g., "SMA Negeri 1 Jakarta")
- `address`: Full school address
- `phone`: Contact phone number

**Sample Data:**
```json
{
  "id": 1,
  "name": "SMA Negeri 1 Jakarta",
  "address": "Jl. Merdeka No. 123, Jakarta Pusat",
  "phone": "021-12345678",
  "created_at": "2025-01-01 10:00:00",
  "updated_at": "2025-01-01 10:00:00"
}
```

---

### 3. categories

**Purpose:** Organizes questions by subject/topic (e.g., Matematika, Fisika, Bahasa Indonesia).

```sql
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Creator of category',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `categories_user_id_index` (`user_id`),
  INDEX `categories_name_index` (`name`),
  
  CONSTRAINT `categories_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `name`: Category name (Matematika, IPA, IPS, etc.)
- `user_id`: Teacher who created the category (NULL for system categories)

**Sample Categories:**
```json
[
  {"id": 1, "name": "Matematika", "user_id": null},
  {"id": 2, "name": "Fisika", "user_id": null},
  {"id": 3, "name": "Bahasa Indonesia", "user_id": null},
  {"id": 4, "name": "Sejarah", "user_id": null},
  {"id": 5, "name": "Biologi", "user_id": null}
]
```

---

## Game Tables

### 4. questions

**Purpose:** Stores trivia questions used in tic-tac-toe gameplay.

```sql
CREATE TABLE `questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `text` TEXT NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `questions_category_id_index` (`category_id`),
  FULLTEXT KEY `questions_text_fulltext` (`text`),
  
  CONSTRAINT `questions_category_id_foreign` 
    FOREIGN KEY (`category_id`) 
    REFERENCES `categories` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `text`: The question content (supports Indonesian language)
- `category_id`: Subject classification

**Sample Question:**
```json
{
  "id": 1,
  "text": "Berapa hasil dari 5 × 8?",
  "category_id": 1,
  "created_at": "2025-01-01 10:00:00",
  "updated_at": "2025-01-01 10:00:00"
}
```

---

### 5. answers

**Purpose:** Multiple-choice answers for questions (typically 4 options per question).

```sql
CREATE TABLE `answers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `answer_text` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `answers_question_id_index` (`question_id`),
  INDEX `answers_is_correct_index` (`is_correct`),
  
  CONSTRAINT `answers_question_id_foreign` 
    FOREIGN KEY (`question_id`) 
    REFERENCES `questions` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `question_id`: Links to parent question
- `answer_text`: The answer option text
- `is_correct`: Boolean flag (only one per question should be TRUE)

**Sample Answers:**
```json
[
  {"id": 1, "question_id": 1, "answer_text": "35", "is_correct": false},
  {"id": 2, "question_id": 1, "answer_text": "40", "is_correct": true},
  {"id": 3, "question_id": 1, "answer_text": "45", "is_correct": false},
  {"id": 4, "question_id": 1, "answer_text": "50", "is_correct": false}
]
```

---

## Tournament Tables

### 6. tournaments

**Purpose:** Tournament/competition management with status tracking.

```sql
CREATE TABLE `tournaments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Tournament organizer',
  `name` VARCHAR(255) NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('draft', 'ongoing', 'completed') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `tournaments_user_id_index` (`user_id`),
  INDEX `tournaments_category_id_index` (`category_id`),
  INDEX `tournaments_status_index` (`status`),
  
  CONSTRAINT `tournaments_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `tournaments_category_id_foreign` 
    FOREIGN KEY (`category_id`) 
    REFERENCES `categories` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `user_id`: Teacher who created the tournament
- `status`: Tournament lifecycle state
  - `draft`: Being configured
  - `ongoing`: Active/in-progress
  - `completed`: Finished

---

### 7. groups

**Purpose:** Teams/groups within tournaments (2-4 players per group).

```sql
CREATE TABLE `groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `groups_tournament_id_index` (`tournament_id`),
  
  CONSTRAINT `groups_tournament_id_foreign` 
    FOREIGN KEY (`tournament_id`) 
    REFERENCES `tournaments` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 8. group_members

**Purpose:** Links users to groups (team membership).

```sql
CREATE TABLE `group_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `group_members_group_id_index` (`group_id`),
  INDEX `group_members_user_id_index` (`user_id`),
  UNIQUE KEY `group_members_unique` (`group_id`, `user_id`),
  
  CONSTRAINT `group_members_group_id_foreign` 
    FOREIGN KEY (`group_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `group_members_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 9. matches

**Purpose:** Individual tic-tac-toe matches between two groups.

```sql
CREATE TABLE `matches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` BIGINT UNSIGNED NOT NULL,
  `group1_id` BIGINT UNSIGNED DEFAULT NULL,
  `group2_id` BIGINT UNSIGNED DEFAULT NULL,
  `winner_id` BIGINT UNSIGNED DEFAULT NULL,
  `round` VARCHAR(255) DEFAULT NULL COMMENT 'Round name (e.g., "Semifinal", "Final")',
  `is_draw` TINYINT(1) NOT NULL DEFAULT 0,
  `turn_number` INT DEFAULT 0 COMMENT 'Current turn in match (0-9)',
  `current_turn_group_id` BIGINT UNSIGNED DEFAULT NULL,
  `tiebreaker_started_at` TIMESTAMP NULL DEFAULT NULL,
  `tiebreaker_group1_score` INT DEFAULT 0,
  `tiebreaker_group2_score` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `matches_tournament_id_index` (`tournament_id`),
  INDEX `matches_group1_id_index` (`group1_id`),
  INDEX `matches_group2_id_index` (`group2_id`),
  INDEX `matches_winner_id_index` (`winner_id`),
  INDEX `matches_current_turn_group_id_index` (`current_turn_group_id`),
  
  CONSTRAINT `matches_tournament_id_foreign` 
    FOREIGN KEY (`tournament_id`) 
    REFERENCES `tournaments` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `matches_group1_id_foreign` 
    FOREIGN KEY (`group1_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `matches_group2_id_foreign` 
    FOREIGN KEY (`group2_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `matches_winner_id_foreign` 
    FOREIGN KEY (`winner_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE SET NULL,
    
  CONSTRAINT `matches_current_turn_group_id_foreign` 
    FOREIGN KEY (`current_turn_group_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Column Details:**
- `turn_number`: Tracks current turn (0-9 for tic-tac-toe)
- `current_turn_group_id`: Which group's turn it is
- `is_draw`: TRUE if match ended in draw
- `tiebreaker_*`: Used when match is tied, implements sudden-death question rounds

---

### 10. user_moves

**Purpose:** Records each move (cell selection) in a match.

```sql
CREATE TABLE `user_moves` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `matches_id` BIGINT UNSIGNED NOT NULL,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `symbol` ENUM('X', 'O') NOT NULL,
  `position` INT NOT NULL COMMENT 'Board position (0-8)',
  `correct` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Was question answered correctly?',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `user_moves_user_id_index` (`user_id`),
  INDEX `user_moves_matches_id_index` (`matches_id`),
  INDEX `user_moves_group_id_index` (`group_id`),
  INDEX `user_moves_position_index` (`position`),
  
  CONSTRAINT `user_moves_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `user_moves_matches_id_foreign` 
    FOREIGN KEY (`matches_id`) 
    REFERENCES `matches` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `user_moves_group_id_foreign` 
    FOREIGN KEY (`group_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Board Position Map:**
```
0 | 1 | 2
---------
3 | 4 | 5
---------
6 | 7 | 8
```

---

### 11. user_joins

**Purpose:** Tracks when users join/spectate a match.

```sql
CREATE TABLE `user_joins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `matches_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `user_joins_user_id_index` (`user_id`),
  INDEX `user_joins_matches_id_index` (`matches_id`),
  UNIQUE KEY `user_joins_unique` (`user_id`, `matches_id`),
  
  CONSTRAINT `user_joins_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `user_joins_matches_id_foreign` 
    FOREIGN KEY (`matches_id`) 
    REFERENCES `matches` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 12. match_helps

**Purpose:** Tracks "help" usage in matches (hint system).

```sql
CREATE TABLE `match_helps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `matches_id` BIGINT UNSIGNED NOT NULL,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `match_helps_matches_id_index` (`matches_id`),
  INDEX `match_helps_group_id_index` (`group_id`),
  
  CONSTRAINT `match_helps_matches_id_foreign` 
    FOREIGN KEY (`matches_id`) 
    REFERENCES `matches` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `match_helps_group_id_foreign` 
    FOREIGN KEY (`group_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Content Management Tables

### 13. cara_bermains (How to Play)

**Purpose:** Tutorial/instruction content for players.

```sql
CREATE TABLE `cara_bermains` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `cara_bermains_title_index` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 14. abouts

**Purpose:** About page content.

```sql
CREATE TABLE `abouts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 15. rich_texts

**Purpose:** Rich text content storage (Trix editor integration).

```sql
CREATE TABLE `rich_texts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `field` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `richable_type` VARCHAR(255) NOT NULL,
  `richable_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `rich_texts_richable_index` (`richable_type`, `richable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Polymorphic Relationship:** Can be attached to any model (questions, cara_bermains, etc.)

---

## System Tables

### 16. password_reset_tokens

```sql
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 17. sessions

```sql
CREATE TABLE `sessions` (
  `id` VARCHAR(255) PRIMARY KEY,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  
  INDEX `sessions_user_id_index` (`user_id`),
  INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 18. cache & cache_locks

```sql
CREATE TABLE `cache` (
  `key` VARCHAR(255) PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  
  INDEX `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 19. jobs & job_batches & failed_jobs

```sql
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) UNIQUE NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `failed_jobs_uuid_index` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 20. Permission Tables (Spatie Laravel-Permission)

```sql
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  INDEX `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
  
  CONSTRAINT `model_has_permissions_permission_id_foreign` 
    FOREIGN KEY (`permission_id`) 
    REFERENCES `permissions` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_roles` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` BIGINT UNSIGNED NOT NULL,
  
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
  
  CONSTRAINT `model_has_roles_role_id_foreign` 
    FOREIGN KEY (`role_id`) 
    REFERENCES `roles` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_has_permissions` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  
  PRIMARY KEY (`permission_id`, `role_id`),
  
  CONSTRAINT `role_has_permissions_permission_id_foreign` 
    FOREIGN KEY (`permission_id`) 
    REFERENCES `permissions` (`id`) 
    ON DELETE CASCADE,
    
  CONSTRAINT `role_has_permissions_role_id_foreign` 
    FOREIGN KEY (`role_id`) 
    REFERENCES `roles` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Foreign Key Relationships

### Cascade Rules Summary

| Parent Table | Child Table | On Delete Rule | Reason |
|--------------|-------------|----------------|--------|
| schools | users | SET NULL | Users can exist without school |
| users | tournaments | CASCADE | Tournament owned by user |
| tournaments | groups | CASCADE | Groups only exist within tournament |
| groups | group_members | CASCADE | Membership deleted with group |
| groups | matches | CASCADE | Match invalid without groups |
| matches | user_moves | CASCADE | Moves deleted with match |
| matches | user_joins | CASCADE | Join records deleted with match |
| questions | answers | CASCADE | Answers only exist for question |
| categories | questions | SET NULL | Questions survive category deletion |

**Key Principles:**
- **CASCADE**: Used when child record is meaningless without parent
- **SET NULL**: Used when child can exist independently
- **RESTRICT**: Not used (would prevent deletion of referenced records)

---

## Index Strategy

### Primary Indexes (Auto-created)

All `id` columns have PRIMARY KEY indexes.

### Foreign Key Indexes

All foreign keys have indexes for join performance:
```sql
-- Example pattern
INDEX `table_foreign_key_index` (`foreign_key_column`)
```

### Custom Indexes

```sql
-- Fast user lookup
INDEX `users_email_index` ON users(email);
INDEX `users_nomor_induk_index` ON users(nomor_induk);

-- Tournament queries
INDEX `tournaments_status_index` ON tournaments(status);

-- Match state queries
INDEX `matches_current_turn_group_id_index` ON matches(current_turn_group_id);

-- Full-text search
FULLTEXT KEY `questions_text_fulltext` ON questions(text);
```

### Composite Indexes

```sql
-- Unique constraints (prevent duplicate memberships)
UNIQUE KEY `group_members_unique` (group_id, user_id);
UNIQUE KEY `user_joins_unique` (user_id, matches_id);

-- Polymorphic relationships
INDEX `rich_texts_richable_index` (richable_type, richable_id);
```

### Performance Tips

1. **Avoid over-indexing**: Each index slows down INSERT/UPDATE
2. **Use EXPLAIN**: Analyze query execution plans
3. **Cover queries**: Include all WHERE/ORDER BY columns in index
4. **Prefix indexes**: For long strings, use prefix length

```sql
-- Example: Index first 50 characters of name
CREATE INDEX schools_name_prefix ON schools(name(50));
```

---

## Migration Order

**Critical: Run migrations in dependency order to avoid foreign key errors.**

```bash
# 1. Base tables (no dependencies)
php artisan migrate --path=database/migrations/0001_01_01_000000_create_users_table.php
php artisan migrate --path=database/migrations/0001_01_01_000001_create_cache_table.php
php artisan migrate --path=database/migrations/0001_01_01_000002_create_jobs_table.php
php artisan migrate --path=database/migrations/2024_09_08_124825_create_permission_tables.php

# 2. Schools (before users modifications)
php artisan migrate --path=database/migrations/2025_03_25_043513_create_schools_table.php
php artisan migrate --path=database/migrations/2025_03_06_064726_add_profile_photo_path_to_users_table.php
php artisan migrate --path=database/migrations/2025_03_25_043747_add_school_id_column_to_users.php

# 3. Categories
php artisan migrate --path=database/migrations/2025_03_16_072549_create_categories_table.php
php artisan migrate --path=database/migrations/2025_03_25_064350_add_user_id_to_categories.php

# 4. Questions & Answers
php artisan migrate --path=database/migrations/2024_09_29_045545_create_questions_table.php
php artisan migrate --path=database/migrations/2025_03_16_072723_add_category_to_questions_table.php
php artisan migrate --path=database/migrations/2024_09_29_045953_create_answers_table.php

# 5. Tournaments & Groups
php artisan migrate --path=database/migrations/2025_02_25_154145_create_tournaments_table.php
php artisan migrate --path=database/migrations/2025_03_16_081046_add_category_to_tournaments_table.php
php artisan migrate --path=database/migrations/2025_02_25_154416_create_groups_table.php
php artisan migrate --path=database/migrations/2025_02_25_154519_create_group_members_table.php

# 6. Matches
php artisan migrate --path=database/migrations/2025_02_25_154613_create_matches_table.php
php artisan migrate --path=database/migrations/2025_02_26_023214_add_round_to_matches_table.php
php artisan migrate --path=database/migrations/2025_04_27_104705_add_is_draw_to_matches_table.php
php artisan migrate --path=database/migrations/2025_05_03_111602_add_turn_fields_to_matches_table.php
php artisan migrate --path=database/migrations/2025_05_03_114545_add_tiebreaker_fields_to_matches_table.php

# 7. Match Details
php artisan migrate --path=database/migrations/2025_02_25_154614_create_user_moves_table.php
php artisan migrate --path=database/migrations/2025_02_25_154615_create_user_joins_table.php
php artisan migrate --path=database/migrations/2025_04_27_044536_create_match_helps_table.php

# 8. Content Management
php artisan migrate --path=database/migrations/2024_09_29_081946_create_cara_bermains_table.php
php artisan migrate --path=database/migrations/2025_05_06_022410_create_abouts_table.php
php artisan migrate --path=database/migrations/2025_03_16_030519_create_rich_texts_table.php

# Or simply run all
php artisan migrate
```

---

## Sample Data Structures

### Complete Tournament Example

```json
{
  "tournament": {
    "id": 1,
    "name": "Turnamen Matematika SMA",
    "category_id": 1,
    "status": "ongoing",
    "user_id": 2
  },
  "groups": [
    {
      "id": 1,
      "tournament_id": 1,
      "name": "Tim Alpha",
      "members": [
        {"user_id": 10, "name": "Budi Santoso"},
        {"user_id": 11, "name": "Ani Wijaya"}
      ]
    },
    {
      "id": 2,
      "tournament_id": 1,
      "name": "Tim Beta",
      "members": [
        {"user_id": 12, "name": "Citra Lestari"},
        {"user_id": 13, "name": "Deni Prasetyo"}
      ]
    }
  ],
  "match": {
    "id": 1,
    "tournament_id": 1,
    "group1_id": 1,
    "group2_id": 2,
    "round": "Final",
    "turn_number": 5,
    "current_turn_group_id": 2,
    "board": ["X", "O", "X", null, "O", null, null, null, "X"]
  }
}
```

### Match Move Example

```json
{
  "user_move": {
    "id": 45,
    "user_id": 10,
    "matches_id": 1,
    "group_id": 1,
    "symbol": "X",
    "position": 8,
    "correct": true,
    "created_at": "2025-02-15 14:30:22"
  },
  "question": {
    "id": 127,
    "text": "Berapakah akar kuadrat dari 144?",
    "category_id": 1
  },
  "answer_selected": {
    "id": 508,
    "answer_text": "12",
    "is_correct": true
  }
}
```

---

## Database Optimization

### Query Optimization

#### 1. Use Eager Loading (N+1 Prevention)

```php
// BAD: N+1 queries
$tournaments = Tournament::all();
foreach ($tournaments as $tournament) {
    echo $tournament->user->name; // Queries for each tournament
}

// GOOD: 2 queries total
$tournaments = Tournament::with('user')->get();
foreach ($tournaments as $tournament) {
    echo $tournament->user->name;
}
```

#### 2. Select Only Needed Columns

```php
// BAD: Fetches all columns
$users = User::all();

// GOOD: Only needed data
$users = User::select('id', 'name', 'email')->get();
```

#### 3. Use Pagination

```php
// For large result sets
$questions = Question::paginate(50);
```

### Database Configuration

**`config/database.php` optimizations:**

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'otaktangkas'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true, // Persistent connections
    ],
],
```

### Caching Strategies

```php
// Cache category list (rarely changes)
$categories = Cache::remember('categories', 3600, function () {
    return Category::all();
});

// Cache user's active tournaments
$userTournaments = Cache::tags(['user:' . $userId])
    ->remember('tournaments', 1800, function () use ($userId) {
        return Tournament::where('user_id', $userId)->get();
    });
```

### Database Maintenance

```bash
# Optimize tables (monthly)
php artisan db:optimize

# Analyze query performance
php artisan telescope:prune

# Clear old sessions (weekly)
php artisan session:gc

# Vacuum deleted records
mysqlcheck -o otaktangkas -u root -p
```

---

## Backup & Restore

### Automated Backup (Daily)

**Laravel Backup Package:**

```bash
composer require spatie/laravel-backup
php artisan backup:run
```

**Backup Configuration (`config/backup.php`):**

```php
'mysql' => [
    'databases' => ['otaktangkas'],
    'exclude_tables' => [
        'sessions',        // Temporary data
        'cache',           // Can be regenerated
        'telescope_entries' // Development only
    ],
],
```

### Manual Backup

```bash
# Full database backup
mysqldump -u root -p otaktangkas > backup_$(date +%Y%m%d_%H%M%S).sql

# Compressed backup
mysqldump -u root -p otaktangkas | gzip > backup_$(date +%Y%m%d).sql.gz

# Backup with structure only (no data)
mysqldump -u root -p --no-data otaktangkas > schema.sql

# Backup specific tables
mysqldump -u root -p otaktangkas users tournaments matches > critical_data.sql
```

### Restore Procedures

```bash
# Restore from backup
mysql -u root -p otaktangkas < backup_20250215.sql

# Restore from compressed
gunzip < backup_20250215.sql.gz | mysql -u root -p otaktangkas

# Restore to different database (testing)
mysql -u root -p otaktangkas_test < backup_20250215.sql
```

### Backup Schedule (Production)

| Frequency | Retention | Storage Location |
|-----------|-----------|------------------|
| Hourly | 24 hours | Local SSD |
| Daily | 7 days | S3 / Cloud Storage |
| Weekly | 4 weeks | Cold Storage |
| Monthly | 12 months | Archive Storage |

### Disaster Recovery

**Recovery Time Objective (RTO):** < 2 hours  
**Recovery Point Objective (RPO):** < 1 hour

**Steps:**
1. Identify last valid backup
2. Spin up new database instance
3. Restore from backup
4. Apply transaction logs (if available)
5. Verify data integrity
6. Switch application connection string
7. Monitor for issues

---

## Database Performance Monitoring

### Key Metrics

```sql
-- Slow queries (> 1 second)
SELECT * FROM information_schema.PROCESSLIST 
WHERE COMMAND != 'Sleep' 
AND TIME > 1;

-- Table sizes
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'otaktangkas'
ORDER BY (data_length + index_length) DESC;

-- Index usage
SELECT 
    table_name,
    index_name,
    cardinality
FROM information_schema.STATISTICS
WHERE table_schema = 'otaktangkas'
ORDER BY cardinality DESC;
```

### Laravel Telescope Integration

```php
// Monitor database queries in real-time
// Access: /telescope/queries
// Shows slow queries, duplicate queries, N+1 issues
```

---

## Troubleshooting

### Common Issues

#### 1. Foreign Key Constraint Failures

```bash
# Check constraint errors
SHOW ENGINE INNODB STATUS;

# Temporarily disable checks (DANGEROUS - dev only)
SET FOREIGN_KEY_CHECKS=0;
# ... your operations ...
SET FOREIGN_KEY_CHECKS=1;
```

#### 2. Migration Failures

```bash
# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed
```

#### 3. Character Encoding Issues

```sql
-- Fix character set for existing table
ALTER TABLE questions 
CONVERT TO CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Check current charset
SHOW VARIABLES LIKE 'character_set%';
```

---

## Best Practices

### Indonesian Education Context

1. **Support NIS/NIK login**: Not all students have email
2. **UTF-8 everywhere**: Indonesian language support
3. **School-based isolation**: Data privacy per school
4. **Offline capabilities**: Cache questions for poor connectivity
5. **Mobile-first design**: Most students use smartphones

### Security

1. **Never store sensitive data unencrypted**
2. **Use database transactions** for multi-table operations
3. **Validate input** before database insertion
4. **Limit database user permissions** (principle of least privilege)
5. **Regular security audits** of query patterns

### Performance

1. **Index foreign keys** (done automatically in Laravel)
2. **Use query caching** for read-heavy tables
3. **Paginate large result sets**
4. **Archive old data** (completed tournaments > 1 year)
5. **Monitor slow query log**

---

## Appendix A: Full Schema Generation Script

```bash
#!/bin/bash
# Generate complete schema from migrations

php artisan migrate:reset
php artisan migrate --pretend > schema_preview.txt
php artisan migrate
php artisan schema:dump
```

---

## Appendix B: Database Seeding

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=QuestionSeeder

# Fresh database with seed data
php artisan migrate:fresh --seed
```

**Seeder Order:**
1. `RoleSeeder` (admin, teacher, student)
2. `SchoolSeeder` (sample schools)
3. `UserSeeder` (test users)
4. `CategorySeeder` (subject categories)
5. `QuestionSeeder` (sample questions)
6. `TournamentSeeder` (demo tournaments)

---

## References

- [Laravel Migrations](https://laravel.com/docs/11.x/migrations)
- [MySQL 8.0 Reference](https://dev.mysql.com/doc/refman/8.0/en/)
- [Spatie Laravel-Permission](https://spatie.be/docs/laravel-permission)
- [Database Normalization](https://en.wikipedia.org/wiki/Database_normalization)

---

**Document Version:** 1.0  
**Maintained By:** OtakTangkas Development Team  
**Last Review:** 2025-02-15
