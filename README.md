# Post Sync + Translation Plugin for WordPress

A custom WordPress plugin that securely syncs posts from a Host site to one or more Target sites and translates content automatically on the Target site using the ChatGPT API.

This plugin uses secure REST API communication, domain-bound authentication, and chunk-based translation to ensure reliability and security.

---

# Features

## Post Synchronization

- Syncs only WordPress **posts**
- Automatically triggers on:
  - Post publish
  - Post update
- Syncs the following fields:
  - Title
  - Content
  - Excerpt
  - Categories
  - Tags
  - Featured Image

---

## Translation (Target Site Only)

- Translation runs only on the Target site
- Uses ChatGPT API configured on Target site
- Supported languages:
  - Hindi
  - French
  - Spanish
- Uses chunk-based translation (1500–2500 characters)
- Preserves HTML structure and formatting

---

## Secure Authentication

- HMAC signature verification
- Unique key per Target site
- Domain binding protection
- Prevents unauthorized sync requests

---

## Category and Tag Sync

- Automatically creates categories if missing
- Automatically creates tags if missing
- Translates categories and tags
- Assigns to synced post

---

## Featured Image Sync

- Downloads image from Host site
- Uploads to Target site media library
- Prevents duplicate image uploads
- Automatically assigns as featured image

---

## Duplicate Prevention

- Maintains mapping between Host and Target posts
- Uses post meta:
  

- Updates existing post instead of creating duplicate

---

## Logging System

Custom database table:


Logs include:

- Host or Target role
- Action performed
- Host post ID
- Target post ID
- Target URL
- Sync status
- Message
- Duration
- Timestamp

---

# How It Works

1. Post is published or updated on Host site
2. Host sends secure REST API request to Target site
3. Target validates:
   - Key
   - Domain
   - Signature
4. Target translates content using ChatGPT API
5. Target creates or updates post
6. Target syncs categories, tags, and featured image
7. Target logs the result

Everything runs in real time.

No cron jobs required.

---

# Installation

Install plugin on both Host and Target sites:


Activate plugin on both sites.

---

# Host Site Setup

Go to: Settings → Post Sync Translate

Add Target:

- Target URL
- Generated Key

Save settings.

---

# Target Site Setup

Go to: Settings → Post Sync Translate


Enter:

- Target Key (from Host)
- Allowed Host Domain
- Translation Language
- ChatGPT API Key

Save settings.

---




