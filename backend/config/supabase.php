<?php
// backend/config/supabase.php
// Supabase Storage Helper for File Uploads

class SupabaseStorage {
    private $supabaseUrl;
    private $supabaseKey;
    
    public function __construct() {
        // Get from environment variables (for Fly.io) or set directly
        $this->supabaseUrl = getenv('SUPABASE_URL') ?: 'https://axcjumdnwtgngxkanfzz.supabase.co';
        
        // IMPORTANT: Use service_role key for backend operations (more permissions)
        // Get this from Supabase Dashboard → Settings → API → service_role key
        $this->supabaseKey = getenv('SUPABASE_SERVICE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF4Y2p1bWRud3Rnbmd4a2FuZnp6Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2ODQwMTkxNCwiZXhwIjoyMDgzOTc3OTE0fQ.Akjz4xB9aURCw5gKJXGDW1kf5s4xyg6cqeeocOtdVUI';
    }
    
    /**
     * Upload file to Supabase Storage
     * 
     * @param string $bucket Bucket name ('assignments', 'submissions', 'lessons', 'profiles')
     * @param string $path File path within bucket (e.g., 'assignment_123.pdf')
     * @param string $localFile Local file path to upload
     * @param string $contentType MIME type (e.g., 'application/pdf')
     * @return array Response with success status and URL
     */
    public function uploadFile($bucket, $path, $localFile, $contentType = 'application/octet-stream') {
        if (!file_exists($localFile)) {
            return [
                'success' => false,
                'error' => 'File not found: ' . $localFile
            ];
        }

        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";
        $fileData = file_get_contents($localFile);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fileData,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->supabaseKey}",
                "Content-Type: {$contentType}",
                "x-upsert: true" // Overwrite if file exists
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $publicUrl = "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
            return [
                'success' => true,
                'url' => $publicUrl,
                'path' => "{$bucket}/{$path}",
                'bucket' => $bucket
            ];
        } else {
            return [
                'success' => false,
                'error' => $response ?: $error,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Upload from $_FILES array
     * 
     * @param string $bucket Bucket name
     * @param array $file $_FILES array element
     * @param string $customPath Optional custom path (if not provided, generates unique name)
     * @return array Upload result
     */
    public function uploadFromRequest($bucket, $file, $customPath = null) {
        // Validate file upload
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'File upload error: ' . ($file['error'] ?? 'Unknown')
            ];
        }

        // Generate path if not provided
        if (!$customPath) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueId = uniqid() . '_' . time();
            $customPath = "{$uniqueId}.{$ext}";
        }

        // Get MIME type
        $contentType = $file['type'] ?? 'application/octet-stream';

        // Upload
        return $this->uploadFile($bucket, $customPath, $file['tmp_name'], $contentType);
    }
    
    /**
     * Delete file from Supabase Storage
     * 
     * @param string $bucket Bucket name
     * @param string $path File path within bucket
     * @return bool Success status
     */
    public function deleteFile($bucket, $path) {
        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->supabaseKey}"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Delete file by full path (e.g., "assignments/file.pdf")
     */
    public function deleteByFullPath($fullPath) {
        $parts = explode('/', $fullPath, 2);
        if (count($parts) !== 2) {
            return false;
        }
        return $this->deleteFile($parts[0], $parts[1]);
    }
    
    /**
     * Get public URL for a file
     * 
     * @param string $bucket Bucket name
     * @param string $path File path
     * @return string Public URL
     */
    public function getPublicUrl($bucket, $path) {
        return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }
    
    /**
     * Get signed URL for private files (expires after specified time)
     * 
     * @param string $bucket Bucket name
     * @param string $path File path
     * @param int $expiresIn Seconds until expiration (default 1 hour)
     * @return array Result with signed URL
     */
    public function getSignedUrl($bucket, $path, $expiresIn = 3600) {
        $url = "{$this->supabaseUrl}/storage/v1/object/sign/{$bucket}/{$path}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['expiresIn' => $expiresIn]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->supabaseKey}",
                "Content-Type: application/json"
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'url' => $this->supabaseUrl . $data['signedURL']
            ];
        } else {
            return [
                'success' => false,
                'error' => $response
            ];
        }
    }
}

// Helper function to get Supabase Storage instance
function getSupabaseStorage() {
    return new SupabaseStorage();
}