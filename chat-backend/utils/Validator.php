<?php
/**
 * Input validation and sanitization helper
 */
class Validator {
    /**
     * Validate email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username (alphanumeric, underscore, 3-50 chars)
     */
    public static function username($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    /**
     * Validate password (min 6 characters)
     */
    public static function password($password) {
        return strlen($password) >= 6;
    }
    
    /**
     * Sanitize string
     */
    public static function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Validate required fields
     */
    public static function required($value) {
        return !empty($value) || $value === '0' || $value === 0;
    }
    
    /**
     * Validate max length
     */
    public static function maxLength($value, $max) {
        return strlen($value) <= $max;
    }
    
    /**
     * Validate min length
     */
    public static function minLength($value, $min) {
        return strlen($value) >= $min;
    }
    
    /**
     * Validate multiple fields
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && !self::required($value)) {
                            $errors[$field][] = ucfirst($field) . ' is required';
                        }
                        break;
                    
                    case 'email':
                        if ($ruleValue && $value && !self::email($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email';
                        }
                        break;
                    
                    case 'username':
                        if ($ruleValue && $value && !self::username($value)) {
                            $errors[$field][] = 'Username must be 3-50 alphanumeric characters or underscores';
                        }
                        break;
                    
                    case 'password':
                        if ($ruleValue && $value && !self::password($value)) {
                            $errors[$field][] = 'Password must be at least 6 characters';
                        }
                        break;
                    
                    case 'min':
                        if ($value && !self::minLength($value, $ruleValue)) {
                            $errors[$field][] = ucfirst($field) . " must be at least $ruleValue characters";
                        }
                        break;
                    
                    case 'max':
                        if ($value && !self::maxLength($value, $ruleValue)) {
                            $errors[$field][] = ucfirst($field) . " must not exceed $ruleValue characters";
                        }
                        break;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}
