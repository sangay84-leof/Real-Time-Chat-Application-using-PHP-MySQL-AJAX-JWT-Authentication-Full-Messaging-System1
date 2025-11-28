<?php
require_once __DIR__ . '/../config/config.php';

/**
 * File upload and management helper
 */
class FileHandler {
    /**
     * Get file type category
     */
    public static function getFileType($mimeType) {
        if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            return 'image';
        } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
            return 'video';
        } elseif (in_array($mimeType, ALLOWED_AUDIO_TYPES)) {
            return 'audio';
        } elseif (in_array($mimeType, ALLOWED_FILE_TYPES)) {
            return 'file';
        }
        return null;
    }
    
    /**
     * Check if file type is allowed
     */
    public static function isAllowedType($mimeType) {
        return self::getFileType($mimeType) !== null;
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
    }
    
    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
        $uniqueId = uniqid() . '_' . time();
        return $basename . '_' . $uniqueId . '.' . $extension;
    }
    
    /**
     * Handle file upload
     */
    public static function upload($file) {
        // Validate file
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('File upload error');
        }
        
        // Check file size
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new Exception('File size exceeds ' . self::formatFileSize(UPLOAD_MAX_SIZE));
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!self::isAllowedType($mimeType)) {
            throw new Exception('File type not allowed');
        }
        
        // Generate unique filename
        $filename = self::generateUniqueFilename($file['name']);
        $uploadPath = UPLOAD_PATH . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save file');
        }
        
        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'type' => self::getFileType($mimeType),
            'url' => '/uploads/' . $filename
        ];
    }
    
    /**
     * Delete file
     */
    public static function delete($filename) {
        $filepath = UPLOAD_PATH . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Get file URL
     */
    public static function getUrl($filename) {
        return APP_URL . '/uploads/' . $filename;
    }
}
