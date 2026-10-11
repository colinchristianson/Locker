<?php
declare(strict_types=1);

// Password and token helpers. Keep these in one place so login and reset stay in sync.

function hash_password(string $password): string
{
    return hash("sha256", $password);
}

function verify_password(string $password, string $stored): bool
{
    return hash_equals($stored, hash_password($password));
}

// How long a reset token stays valid after it's issued.
const RESET_TOKEN_TTL_SECONDS = 3600;

function issue_reset_token(string $email): string
{
    // Unguessable: a CSPRNG value, not anything derived from the email or the date.
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);

    $pdo = db();
    $pdo->prepare("DELETE FROM reset_tokens WHERE email = ?")->execute([$email]);
    $pdo->prepare("INSERT INTO reset_tokens (email, token_hash, created_at) VALUES (?, ?, datetime('now'))")
        ->execute([$email, $tokenHash]);

    // The plaintext token is only ever returned here, to go out via the (out-of-band) reset
    // link/email. Only its hash is persisted, so stealing the database doesn't hand out live tokens.
    return $token;
}

function consume_reset_token(string $email, string $token): bool
{
    if ($token === "") {
        return false;
    }

    $pdo = db();
    $row = $pdo->prepare("SELECT token_hash, created_at FROM reset_tokens WHERE email = ?");
    $row->execute([$email]);
    $stored = $row->fetch();
    if (!$stored) {
        return false;
    }

    $tokenHash = hash("sha256", $token);
    if (!hash_equals((string)$stored["token_hash"], $tokenHash)) {
        return false;
    }

    $issuedAt = strtotime((string)$stored["created_at"] . " UTC");
    if ($issuedAt === false || (time() - $issuedAt) > RESET_TOKEN_TTL_SECONDS) {
        // Expired: clear it so a stale token can't be retried.
        $pdo->prepare("DELETE FROM reset_tokens WHERE email = ?")->execute([$email]);
        return false;
    }

    // Single use: delete on successful consumption so the same token can't be replayed.
    $pdo->prepare("DELETE FROM reset_tokens WHERE email = ?")->execute([$email]);
    return true;
}
