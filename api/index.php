<?php

// Vercel serverless entry point (vercel-php runtime).
// Every request is routed here by vercel.json and forwarded to
// Laravel's real front controller.
require __DIR__.'/../public/index.php';
