<?php
// csrf.php
session_start();

/**
 * Generate a CSRF token and store it in session.
 * Call this before rendering your form.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the token from a submitted form.
 */
function validateCsrfToken($tokenFromForm): bool
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $tokenFromForm);
}
