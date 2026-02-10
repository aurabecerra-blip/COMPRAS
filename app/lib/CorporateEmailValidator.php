<?php
class CorporateEmailValidator
{
    public static function isValid(string $email): bool
    {
        $normalized = strtolower(trim($email));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return (bool)preg_match('/^[^@\s]+@aossas\.com$/i', $normalized);
    }
}
