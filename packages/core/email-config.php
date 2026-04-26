<?php
/**
 * Email Configuration for the Library Booking System.
 *
 * HOW TO SET UP GMAIL SMTP:
 * ─────────────────────────
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification (required for App Passwords).
 * 3. Go to https://myaccount.google.com/apppasswords
 *    (Search "App Passwords" in Google Account settings if the link doesn't work.)
 * 4. Select "Mail" as the app, and "Windows Computer" as the device.
 * 5. Click "Generate" — Google will give you a 16-character password (e.g. "abcd efgh ijkl mnop").
 * 6. Copy that password (without spaces) and paste it below as MAIL_PASSWORD.
 *
 * WHAT TO CHANGE BELOW:
 * ─────────────────────
 * - MAIL_USERNAME: Your full Gmail address (e.g. 'youremail@gmail.com')
 * - MAIL_PASSWORD: The 16-char App Password from step 5 (NOT your Gmail login password)
 * - MAIL_FROM_EMAIL: Usually same as MAIL_USERNAME
 * - MAIL_FROM_NAME: The display name recipients will see (e.g. 'AUF Library')
 */

return [
    // ┌──────────────────────────────────────────────────────────┐
    // │  SMTP Server Settings (Gmail defaults — change if       │
    // │  you're using a different provider like Outlook/Yahoo)  │
    // └──────────────────────────────────────────────────────────┘
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => 587,
    'MAIL_ENCRYPTION' => 'tls',    // 'tls' for port 587, 'ssl' for port 465

    // ┌──────────────────────────────────────────────────────────┐
    // │  ⚠️  CHANGE THESE TWO VALUES TO YOUR OWN CREDENTIALS    │
    // └──────────────────────────────────────────────────────────┘
    'MAIL_USERNAME' => 'bud.ai.devteam@gmail.com',           // ← PUT YOUR GMAIL ADDRESS HERE
    'MAIL_PASSWORD' => 'whkgpbyzjjanmqqc',      // ← PUT YOUR APP PASSWORD HERE

    // ┌──────────────────────────────────────────────────────────┐
    // │  Sender Identity (what the recipient sees)              │
    // └──────────────────────────────────────────────────────────┘
    'MAIL_FROM_EMAIL' => 'bud.ai.devteam@gmail.com',           // ← SAME AS MAIL_USERNAME (usually)
    'MAIL_FROM_NAME' => 'AUF Library (Bud AI Team)',                    // ← Change display name if needed

    // ┌──────────────────────────────────────────────────────────┐
    // │  Reply-To address (where student replies go)            │
    // └──────────────────────────────────────────────────────────┘
    'MAIL_REPLY_TO' => 'bud.ai.devteam@gmail.com',             // ← Change if needed

    // ┌──────────────────────────────────────────────────────────┐
    // │  Debug level (set to 0 for production, 2 for testing)   │
    // │  0 = Off, 1 = Client msgs, 2 = Client + Server msgs    │
    // └──────────────────────────────────────────────────────────┘
    'MAIL_DEBUG' => 0,
];
