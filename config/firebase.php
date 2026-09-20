<?php

// Isolates all Firebase configuration in one place. Nothing in Phase 1
// reads from this file yet — it exists so the Phase 7 real-time layer
// (announcements, result-publish events, notifications) has a single,
// already-reviewed place to plug into.
//
// Reminder: MySQL remains authoritative. Firebase is written to *after*
// a MySQL transaction commits, never before, and is never read back as
// the source of truth for any authorization or academic decision.

return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'), // absolute path to the service-account JSON, kept outside web root
    'database_url' => env('FIREBASE_DATABASE_URL'),
];
