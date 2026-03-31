<?php

/**
 * Handle file uploads and processing.
 */
if (!defined('GRINDS_APP'))
    exit;

class FileManager
{
    public const MAX_WIDTH = 1920;
    public const QUALITY = 85;
    public const MAX_PIXELS = 25000000;
    public const MAX_DIMENSION = 10000;

    // Animated GIF: maximum frame count allowed on upload
    public const MAX_GIF_FRAMES = 300;

    // Thumbnail settings
    public const THUMB_SIZE = 300;
    public const THUMB_QUALITY = 80;

    private static $mime_map = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
        'image/x-icon' => ['ico'],
        'image/vnd.microsoft.icon' => ['ico'],
        'image/svg+xml' => ['svg'],

        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.ms-powerpoint' => ['ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],

        'text/plain' => ['txt', 'csv', 'md', 'log', 'json', 'xml'],
        'text/csv' => ['csv'],
        'text/markdown' => ['md'],
        'application/json' => ['json'],
        'application/xml' => ['xml'],
        'text/xml' => ['xml'],

        'application/zip' => ['zip'],
        'application/x-zip-compressed' => ['zip'],
        'application/octet-stream' => ['zip', 'csv'],
        'application/x-rar-compressed' => ['rar'],
        'application/x-7z-compressed' => ['7z'],

        'audio/mpeg' => ['mp3'],
        'audio/mp3' => ['mp3'],
        'audio/wav' => ['wav'],
        'audio/x-wav' => ['wav'],
        'audio/ogg' => ['ogg'],
        'audio/mp4' => ['m4a'],
        'audio/x-m4a' => ['m4a'],

        'video/mp4' => ['mp4'],
        'video/mpeg' => ['mpeg', 'mpg'],
        'video/webm' => ['webm'],
        'video/ogg' => ['ogv'],
        'video/quicktime' => ['mov'],
        'video/x-msvideo' => ['avi'],
    ];

    /**
     * Get list of allowed extensions.
     */
    public static function getAllowedExtensions()
    {
        return array_unique(array_merge(...array_values(self::$mime_map)));
    }

    /**
     * Get MIME type for extension.
     *
     * @param string $ext File extension.
     * @return string|null MIME type or null if not found.
     */
    public static function getMimeType($ext)
    {
        $ext = strtolower($ext ?? '');
        foreach (self::$mime_map as $mime => $extensions) {
            if (in_array($ext, $extensions)) {
                return $mime;
            }
        }
        return null;
    }
    /**
     * Get compatible MIME types for an extension.
     *
     * @param string $ext File extension.
     * @return array List of compatible MIME types.
     */
    public static function getCompatibleMimes($ext)
    {
        $ext = strtolower($ext ?? '');
        $mimes = [];
        foreach (self::$mime_map as $mime => $extensions) {
            if (in_array($ext, $extensions)) {
                $mimes[] = $mime;
            }
        }
        return $mimes;
    }


    /**
     * Handles file upload.
     */
    public static function handleUpload($file, $pdo)
    {
        $uploadResult = self::upload($file);
        $tempPath = $uploadResult['temp_path'] ?? null;
        $rawMeta = $uploadResult['metadata'] ?? [];
        $mime = $uploadResult['mime'] ?? '';
        $ext = $uploadResult['ext'] ?? '';
        $imageSize = $uploadResult['image_size'] ?? null;

        if (!$tempPath || !file_exists($tempPath))
            return false;

        $metaToSave = [];
        $metaToSave['license'] = 'unknown';

        // Generate metadata
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $readableName = ucfirst(str_replace(['-', '_'], ' ', $baseName));
        $metaToSave['alt'] = $readableName;
        $metaToSave['title'] = $readableName;
        $metaToSave['original_name'] = $file['name'];

        // Detect AI metadata
        self::detectAiMetadata($rawMeta, $metaToSave);

        try {
            // Determine target directory
            $subDir = date('Y') . '/' . date('m') . '/';
            $uploadDir = ROOT_PATH . '/assets/uploads/' . $subDir;
            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception(_t('err_failed_create_dir'));
            }

            // Prevent double extension
            $baseName = str_replace('.', '_', $baseName);

            // Sanitize filename (ASCII only to avoid NFD/NFC issues)
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $baseName);

            // Ensure safe name length and uniqueness
            if (empty($safeName) || strlen($safeName) < 3) {
                $safeName = 'media_' . bin2hex(random_bytes(6));
            } else {
                $safeName = substr($safeName, 0, 30) . '_' . bin2hex(random_bytes(4));
            }

            $newFilename = $safeName . '.' . $ext;

            // Check for potential WebP collision for images
            $checkWebp = ($ext !== 'webp' && str_starts_with($mime, 'image/'));

            $isCollision = function ($name) use ($uploadDir, $checkWebp) {
                if (file_exists($uploadDir . $name))
                    return true;
                if ($checkWebp) {
                    $webpName = pathinfo($name, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($uploadDir . $webpName))
                        return true;
                }
                return false;
            };

            // Handle duplicates
            if ($isCollision($newFilename)) {
                $newFilename = $safeName . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                while ($isCollision($newFilename)) {
                    $newFilename = $safeName . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                }
            }

            $finalRelativePath = 'assets/uploads/' . $subDir . $newFilename;
            $fullFinalPath = ROOT_PATH . '/' . $finalRelativePath;

            $hasGd = extension_loaded('gd');
            $hasImagick = extension_loaded('imagick') && class_exists('Imagick');

            // Process image
            $processMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
            if ($hasImagick) {
                $processMimes[] = 'image/gif';
            }

            if (($hasGd || $hasImagick) && in_array($mime, $processMimes)) {
                try {
                    $procResult = self::processImage($tempPath, $mime, $imageSize, $hasImagick, $hasGd);
                    if ($procResult) {
                        if (is_array($imageSize)) {
                            $imageSize[0] = $procResult['width'];
                            $imageSize[1] = $procResult['height'];
                        } else {
                            $imageSize = [$procResult['width'], $procResult['height'], 'mime' => $mime];
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception(_t('err_image_processing_failed') . ": " . $e->getMessage());
                }
            }

            // Move file
            if (!@rename($tempPath, $fullFinalPath)) {
                if (copy($tempPath, $fullFinalPath)) {
                    grinds_force_unlink($tempPath);
                } else {
                    throw new Exception(_t('err_failed_move_file'));
                }
            }

            // Move generated WebP file if exists
            $tempWebpPath = pathinfo($tempPath, PATHINFO_DIRNAME) . '/' . pathinfo($tempPath, PATHINFO_FILENAME) . '.webp';
            if ($ext !== 'webp' && file_exists($tempWebpPath)) {
                $finalWebpPath = pathinfo($fullFinalPath, PATHINFO_DIRNAME) . '/' . pathinfo($fullFinalPath, PATHINFO_FILENAME) . '.webp';
                if (@rename($tempWebpPath, $finalWebpPath)) {
                    $metaToSave['has_webp'] = true;
                } elseif (@copy($tempWebpPath, $finalWebpPath)) {
                    grinds_force_unlink($tempWebpPath);
                    $metaToSave['has_webp'] = true;
                }
            }

            $path = $finalRelativePath;
            $filename = $newFilename;

            // Generate thumbnail
            if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml' && ($hasGd || $hasImagick)) {
                $thumbPath = self::createThumbnail(ROOT_PATH . '/' . $path, $imageSize, $hasImagick, $hasGd);
                if ($thumbPath) {
                    $metaToSave['thumbnail'] = $thumbPath;
                }
            }

            // Get dimensions
            $fullFinalPath = ROOT_PATH . '/' . $path;
            if ($imageSize) {
                $metaToSave['width'] = $imageSize[0];
                $metaToSave['height'] = $imageSize[1];
            }

            // Clear stat cache to ensure accurate filesize
            clearstatcache(true, $fullFinalPath);
            $finalSize = file_exists($fullFinalPath) ? filesize($fullFinalPath) : (int)$file['size'];

            $stmt = $pdo->prepare("INSERT INTO media (filename, filepath, file_type, file_size, metadata, uploaded_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $filename,
                $path,
                $mime,
                $finalSize,
                json_encode($metaToSave, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            if (isset($tempPath) && file_exists($tempPath))
                grinds_force_unlink($tempPath);
            if (isset($finalRelativePath))
                self::delete($finalRelativePath);

            if ($e instanceof PDOException) {
                error_log("FileManager DB Error: " . $e->getMessage());
                throw new Exception(_t('err_db_error'));
            }

            error_log("FileManager Error: " . $e->getMessage());
            throw $e;
        }

        return $path;
    }

    /**
     * Creates thumbnail.
     */
    private static function createThumbnail($sourceFullPath, $imageSize = null, $hasImagick = null, $hasGd = null)
    {
        $info = pathinfo($sourceFullPath);
        $thumbName = $info['filename'] . '_thumb.webp';
        $thumbFullPath = $info['dirname'] . '/' . $thumbName;

        try {
            // Check memory
            $size = $imageSize;
            if (!$size) {
                $size = @getimagesize($sourceFullPath);
            }

            $mime = self::getMimeType(pathinfo($sourceFullPath, PATHINFO_EXTENSION));

            if ($size) {
                if (!self::checkMemoryRequirement($size[0], $size[1], $mime)) {
                    throw new Exception(_t('err_image_memory_limit'));
                }
            }

            $resultPath = self::executeImageOperation($sourceFullPath, function ($sourceFullPath) use ($thumbFullPath) {
                // Use Imagick
                $className = '\Imagick';
                $img = new $className($sourceFullPath . '[0]');

                if (method_exists($img, 'autoOrient')) {
                    $img->autoOrient();
                }

                // Preserve transparency
                $pixelClass = '\ImagickPixel';
                $img->setImageBackgroundColor(new $pixelClass('rgba(0, 0, 0, 0)'));
                if (method_exists($img, 'setImageAlphaChannel')) {
                    // Check constant for compatibility
                    $alphaSet = defined('\Imagick::ALPHACHANNEL_SET') ? constant('\Imagick::ALPHACHANNEL_SET') : 1;
                    $img->setImageAlphaChannel($alphaSet);
                }

                // Resize
                $img->thumbnailImage(self::THUMB_SIZE, self::THUMB_SIZE, true);

                $img->setImageFormat('webp');
                $img->setImageCompressionQuality(self::THUMB_QUALITY);
                $img->writeImage($thumbFullPath);

                if (!file_exists($thumbFullPath) || filesize($thumbFullPath) === 0) {
                    throw new Exception("ImageMagick created empty file");
                }

                $img->clear();
                $img->destroy();

                return $thumbFullPath;
            }, function ($sourceFullPath, $size) use ($thumbFullPath) {
                // Use GD
                $mime = (is_array($size) && isset($size['mime'])) ? $size['mime'] : self::getMimeType(pathinfo($sourceFullPath, PATHINFO_EXTENSION));
                $srcImg = self::gdLoadImage($sourceFullPath, $mime);

                if (!$srcImg)
                    return null;

                $width = imagesx($srcImg);
                $height = imagesy($srcImg);

                // Calculate dimensions
                $targetSize = self::THUMB_SIZE;
                $ratio = $width / $height;

                if ($width > $height) {
                    $newWidth = $targetSize;
                    $newHeight = floor($targetSize / $ratio);
                } else {
                    $newHeight = $targetSize;
                    $newWidth = floor($targetSize * $ratio);
                }

                $thumbImg = imagecreatetruecolor($newWidth, $newHeight);
                if (!$thumbImg) {
                    throw new Exception(_t('err_image_memory_limit'));
                }

                // Handle transparency
                if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif' || $mime == 'image/avif') {
                    imagealphablending($thumbImg, false);
                    imagesavealpha($thumbImg, true);
                    $transparent = imagecolorallocatealpha($thumbImg, 255, 255, 255, 127);
                    imagefilledrectangle($thumbImg, 0, 0, $newWidth, $newHeight, $transparent);
                }

                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // Save as WebP
                if (function_exists('imagewebp')) {
                    @imagewebp($thumbImg, $thumbFullPath, self::THUMB_QUALITY);
                }

                // Fallback to JPEG
                if (!file_exists($thumbFullPath) || filesize($thumbFullPath) === 0) {
                    if (file_exists($thumbFullPath)) {
                        grinds_force_unlink($thumbFullPath);
                    }
                    $thumbFullPath = preg_replace('/\.webp$/', '.jpg', $thumbFullPath);
                    imagejpeg($thumbImg, $thumbFullPath, self::THUMB_QUALITY);
                }

                // Cleanup
                unset($srcImg);
                unset($thumbImg);

                if (!file_exists($thumbFullPath)) {
                    return null;
                }

                return $thumbFullPath;
            }, $size, $hasImagick, $hasGd);

            if ($resultPath) {
                $rootLen = strlen(ROOT_PATH) + 1;
                return substr($resultPath, $rootLen);
            }
            return null;
        } catch (Exception $e) {
            error_log("Thumbnail creation skipped: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Detects AI metadata.
     */
    private static function detectAiMetadata($rawMeta, &$metaToSave)
    {
        // Check filename patterns
        if (isset($metaToSave['original_name'])) {
            if (str_starts_with(strtolower($metaToSave['original_name']), 'gemini_generated_image')) {
                $metaToSave['is_ai'] = true;
                $metaToSave['source'] = 'Google Gemini';
            } elseif (str_starts_with(strtolower($metaToSave['original_name']), 'dall·e') || str_starts_with(strtolower($metaToSave['original_name']), 'dall-e')) {
                $metaToSave['is_ai'] = true;
                $metaToSave['source'] = 'DALL-E';
            } elseif (str_starts_with(strtolower($metaToSave['original_name']), 'chatgpt image')) {
                $metaToSave['is_ai'] = true;
                $metaToSave['source'] = 'ChatGPT (DALL-E)';
            }
        }
        if (!empty($rawMeta['XMP_RAW'])) {
            $xmp = $rawMeta['XMP_RAW'];
            if (str_contains(strtolower($xmp), 'draw things')) {
                $metaToSave['source'] = 'Draw Things';
                $metaToSave['is_ai'] = true;
            } elseif (str_contains(strtolower($xmp), 'stable diffusion')) {
                $metaToSave['source'] = 'Stable Diffusion';
                $metaToSave['is_ai'] = true;
            } elseif (str_contains(strtolower($xmp), 'made with google ai') || str_contains(strtolower($xmp), 'imagen')) {
                $metaToSave['source'] = 'Google AI';
                $metaToSave['is_ai'] = true;
            } elseif (str_contains(strtolower($xmp), 'dall-e')) {
                $metaToSave['source'] = 'DALL-E';
                $metaToSave['is_ai'] = true;
            } elseif (str_contains(strtolower($xmp), 'adobe firefly')) {
                $metaToSave['source'] = 'Adobe Firefly';
                $metaToSave['is_ai'] = true;
            } elseif (str_contains(strtolower($xmp), 'nano banana')) {
                $metaToSave['source'] = 'Nano Banana';
                $metaToSave['is_ai'] = true;
            }
            if (preg_match('/<dc:description>\s*<rdf:Alt>\s*<rdf:li[^>]*>(.*?)<\/rdf:li>/s', $xmp, $m)) {
                $metaToSave['prompt'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
                $metaToSave['is_ai'] = true;
            }
            if (preg_match('/<exif:UserComment>\s*<rdf:Alt>\s*<rdf:li[^>]*>(.*?)<\/rdf:li>/s', $xmp, $m)) {
                $json = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
                if (is_array($json)) {
                    $metaToSave['is_ai'] = true;
                    if (isset($json['model']))
                        $metaToSave['model'] = $json['model'];
                }
            }
        }

        if (!empty($rawMeta) && !isset($metaToSave['prompt'])) {
            foreach ($rawMeta as $key => $val) {
                if ($key === 'XMP_RAW')
                    continue;

                // Check for specific AI signatures in metadata values
                if (str_contains(strtolower($val), 'made with google ai')) {
                    $metaToSave['is_ai'] = true;
                    $metaToSave['source'] = 'Google AI';
                } elseif (str_contains(strtolower($val), 'dall-e')) {
                    $metaToSave['is_ai'] = true;
                    $metaToSave['source'] = 'DALL-E';
                } elseif (str_contains(strtolower($val), 'nano banana')) {
                    $metaToSave['is_ai'] = true;
                    $metaToSave['source'] = 'Nano Banana';
                }

                // Detect generic AI
                if (preg_match('/^(software|make|model|generator)$/i', $key)) {
                    if (preg_match('/\b(AI|Diffusion|Midjourney|Firefly|Bing|Copilot|GenAI)\b/i', $val)) {
                        $metaToSave['is_ai'] = true;
                        if (!isset($metaToSave['source'])) {
                            $metaToSave['source'] = trim($val);
                        }
                    }
                }
                if (str_contains(strtolower($val), 'ai generated')) {
                    $metaToSave['is_ai'] = true;
                }

                if (
                    str_contains(strtolower($key), 'parameters') ||
                    str_contains(strtolower($val), 'steps:') ||
                    str_contains(strtolower($val), 'seed:') ||
                    str_contains(strtolower($val), 'negative prompt:') ||
                    str_contains(strtolower($val), 'model:')
                ) {
                    $metaToSave['is_ai'] = true;
                    if (!isset($metaToSave['prompt']) || strlen($val) > strlen($metaToSave['prompt'])) {
                        $metaToSave['prompt'] = $val;
                    }
                    if (str_contains(strtolower($val), 'draw things'))
                        $metaToSave['source'] = 'Draw Things';
                    if (str_contains(strtolower($val), 'midjourney'))
                        $metaToSave['source'] = 'Midjourney';
                }
            }
        }
    }

    private static function upload($file)
    {
        $imageSize = null;
        // Increase memory limit
        if (function_exists('grinds_set_high_load_mode')) {
            grinds_set_high_load_mode();
        }

        if (empty($file['name']) || !is_string($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['temp_path' => '', 'metadata' => []];
        }

        // Validate filename
        if (str_contains($file['name'], "\0")) {
            throw new Exception(_t('err_invalid_filename'));
        }

        $tmpPath = $file['tmp_name'];

        // Validate size
        $maxSize = function_exists('grinds_get_max_upload_size') ? grinds_get_max_upload_size() : 50 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $msg = str_replace('%s', round($maxSize / 1024 / 1024) . 'MB', _t('js_file_too_large'));
            throw new Exception($msg);
        }

        // Validate Extension (Reverse Lookup)
        $uploadedExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validMimes = self::getCompatibleMimes($uploadedExt);

        if (empty($validMimes)) {
            error_log("Security: Blocked upload of prohibited extension '{$uploadedExt}'");
            throw new Exception(_t('err_file_type') . " (Ext: {$uploadedExt})");
        }

        // Detect MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);

        // Handle SVG MIME
        if ($uploadedExt === 'svg' && in_array($mime, ['text/plain', 'text/xml'])) {
            $header = file_get_contents($tmpPath, false, null, 0, 256);
            if ($header !== false && (stripos($header, '<svg') !== false || stripos($header, '<?xml') !== false)) {
                $mime = 'image/svg+xml';
            }
        }

        // Validate MIME against Extension
        if (!in_array($mime, $validMimes)) {
            error_log("Security: Extension spoofing detected. Ext: {$uploadedExt}, MIME: {$mime}");
            throw new Exception(_t('err_file_type') . " (MIME: {$mime}, Ext: {$uploadedExt})");
        }

        if ($mime === 'image/svg+xml') {
            // Use capability check instead of hardcoded role
            if (!function_exists('current_user_can') || !current_user_can('manage_settings')) {
                throw new Exception(_t('err_svg_admin_only'));
            }

            if (!extension_loaded('dom')) {
                throw new Exception(_t('err_dom_ext_missing'));
            }
        }

        $ext = $uploadedExt;

        // Check pixel flood
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'])) {
            // Memory exhaustion defense: Reject excessively large files before getimagesize() reads them
            if ($file['size'] > 20 * 1024 * 1024) { // 20MB hard limit for image processing
                throw new Exception(_t('err_image_memory_limit') . ' (File size exceeds 20MB limit)');
            }

            $size = @getimagesize($tmpPath);
            if ($size) {
                $imageSize = $size;
                self::checkMemoryRequirement($size[0], $size[1], $mime);
            } else {
                throw new Exception(_t('err_image_corrupt'));
            }
        }

        // Animated GIF: frame-count check (prevents memory exhaustion via coalesceImages)
        if ($mime === 'image/gif' && extension_loaded('imagick') && class_exists('Imagick')) {
            try {
                $className = '\Imagick';
                $gifPing = new $className();
                // pingImage reads only metadata (no pixel decoding) — fast and low-memory
                $gifPing->pingImage($tmpPath);
                $frameCount = $gifPing->getNumberImages();
                $gifPing->clear();
                $gifPing->destroy();

                if ($frameCount > self::MAX_GIF_FRAMES) {
                    throw new Exception(sprintf(
                        _t('err_gif_too_many_frames'),
                        $frameCount,
                        self::MAX_GIF_FRAMES
                    ), 413);
                }

                // Scale the memory estimate for frame expansion during coalesceImages
                // Accurately estimate for all frames to prevent OOM crashes
                if ($frameCount > 1 && $imageSize) {
                    self::checkMemoryRequirement(
                        $imageSize[0] * $frameCount,
                        $imageSize[1],
                        $mime
                    );
                }
            } catch (Exception $e) {
                // Re-throw security-related exceptions (memory limit, frame count)
                if ($e->getCode() === 413) {
                    throw $e; // Re-throw security exceptions
                }
                // Non-fatal: if Imagick cannot ping the file, fall through gracefully
                error_log('GIF frame check skipped: ' . $e->getMessage());
            }
        }

        // Scan content security
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/svg+xml', 'text/plain', 'text/csv', 'text/markdown', 'application/json', 'application/xml', 'text/xml'])) {
            $isSvg = ($mime === 'image/svg+xml');
            $isText = in_array($mime, ['text/plain', 'text/csv', 'text/markdown', 'application/json', 'application/xml', 'text/xml']);
            $scanLimit = ($isSvg || $isText) ? 2 * 1024 * 1024 : 1024;
            $content = file_get_contents($tmpPath, false, null, 0, $scanLimit);

            // Check PHP tags
            if (preg_match('/<\?(?!xml)/', $content)) {
                throw new Exception(_t('err_security_malicious_code'));
            }

            // Check SVG
            if ($isSvg) {
                // SVG content will be sanitized later by sanitizeSvg()
            } elseif (str_contains(strtolower($content), '<script')) {
                throw new Exception(_t('err_security_malicious_code'));
            }
        }

        // Extract metadata
        $metadata = [];
        try {
            if ($mime === 'image/png') {
                $metadata = self::extractPngMetadata($tmpPath);
            } elseif ($mime === 'image/jpeg') {
                $metadata = self::extractJpegMetadata($tmpPath);
            }
            $xmpData = self::extractXmpRaw($tmpPath);
            if (!empty($xmpData)) {
                $metadata['XMP_RAW'] = $xmpData;
            }
        } catch (Exception $e) {
            error_log("Metadata extraction failed: " . $e->getMessage());
        }

        // Use temp directory
        $tempDir = ROOT_PATH . '/data/tmp/uploads/';
        if (!grinds_secure_dir($tempDir)) {
            throw new Exception(_t('err_failed_create_dir'));
        }

        // Generate filename
        $tempFilename = 'upload_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadPath = $tempDir . $tempFilename;

        if (!move_uploaded_file($tmpPath, $uploadPath)) {
            throw new Exception(_t('err_failed_move_file'));
        }

        // Sanitize SVG
        if ($mime === 'image/svg+xml') {
            if (!self::sanitizeSvg($uploadPath)) {
                grinds_force_unlink($uploadPath);
                throw new Exception(_t('err_file_type'));
            }
        }

        return [
            'temp_path' => $uploadPath,
            'metadata' => $metadata,
            'mime' => $mime,
            'ext' => $ext,
            'image_size' => $imageSize
        ];
    }

    /**
     * Process image.
     */
    private static function processImage($filePath, $mime, $imageSize = null, $hasImagick = null, $hasGd = null)
    {
        $maxWidth = function_exists('get_option') ? (int)get_option('media_max_width', self::MAX_WIDTH) : self::MAX_WIDTH;
        if ($maxWidth <= 0) $maxWidth = self::MAX_WIDTH;

        $quality = function_exists('get_option') ? (int)get_option('media_quality', self::QUALITY) : self::QUALITY;
        if ($quality <= 0 || $quality > 100) $quality = self::QUALITY;

        try {
            return self::executeImageOperation($filePath, function ($filePath) use ($mime, $maxWidth, $quality) {
                // Use Imagick
                $className = '\Imagick';
                $img = new $className($filePath);

                // Handle animation
                $img = $img->coalesceImages();

                $finalWidth = 0;
                $finalHeight = 0;

                foreach ($img as $frame) {
                    // Auto-rotate
                    if (method_exists($frame, 'autoOrient')) {
                        $frame->autoOrient();
                    }

                    // Preserve transparency
                    $pixelClass = '\ImagickPixel';
                    $frame->setImageBackgroundColor(new $pixelClass('transparent'));
                    if (method_exists($frame, 'setImageAlphaChannel')) {
                        // Check constant for compatibility
                        $alphaSet = defined('\Imagick::ALPHACHANNEL_SET') ? constant('\Imagick::ALPHACHANNEL_SET') : 1;
                        $frame->setImageAlphaChannel($alphaSet);
                    }

                    // Resize
                    $width = $frame->getImageWidth();
                    if ($width > $maxWidth) {
                        $filter = defined('Imagick::FILTER_LANCZOS') ? constant('Imagick::FILTER_LANCZOS') : 22;
                        $frame->resizeImage($maxWidth, 0, $filter, 1);
                    }

                    // Strip metadata
                    $frame->stripImage();
                }

                $finalWidth = $img->getImageWidth();
                $finalHeight = $img->getImageHeight();

                // Save
                $img = $img->deconstructImages();

                // Create WebP first to preserve pristine memory quality
                if ($mime !== 'image/webp') {
                    $pathInfo = pathinfo($filePath);
                    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
                    $webpImg = clone $img;
                    $webpImg->setImageFormat('webp');
                    $webpImg->setImageCompressionQuality($quality);
                    $webpImg->writeImages($webpPath, true);
                    $webpImg->clear();
                    $webpImg->destroy();
                }

                // Save original format
                $img->writeImages($filePath, true);

                $img->clear();
                $img->destroy();

                return ['width' => $finalWidth, 'height' => $finalHeight];
            }, function ($filePath, $size) use ($mime, $maxWidth, $quality) {
                // Use GD
                $image = self::gdLoadImage($filePath, $mime);

                if (!$image) {
                    // This case handles when imagecreatefrom* returns false without throwing an error (e.g., corrupt file)
                    throw new Exception(_t('err_image_corrupt'));
                }

                // Check orientation
                $orientation = 1;
                if ($mime === 'image/jpeg' && extension_loaded('exif') && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($filePath);
                    if (!empty($exif['Orientation'])) {
                        $orientation = $exif['Orientation'];
                    }
                }

                $width = imagesx($image);
                $height = imagesy($image);

                // Determine visual width
                $isVertical = in_array($orientation, [5, 6, 7, 8]);
                $visualWidth = $isVertical ? $height : $width;

                // Resize
                if ($visualWidth > $maxWidth) {
                    $scale = $maxWidth / $visualWidth;
                    $newWidth = floor($width * $scale);
                    $newHeight = floor($height * $scale);

                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    if (!$newImage) {
                        throw new Exception(_t('err_image_memory_limit'));
                    }

                    if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif' || $mime === 'image/avif') {
                        imagealphablending($newImage, false);
                        imagesavealpha($newImage, true);
                        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                    }

                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    unset($image);
                    $image = $newImage;

                    $width = $newWidth;
                    $height = $newHeight;
                }

                // Rotate
                if ($orientation !== 1 && function_exists('imagerotate')) {
                    $deg = 0;
                    switch ($orientation) {
                        case 3:
                            $deg = 180;
                            break;
                        case 6:
                            $deg = -90;
                            break;
                        case 8:
                            $deg = 90;
                            break;
                    }
                    if ($deg !== 0) {
                        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
                        $rotated = imagerotate($image, $deg, $transparent);
                        if ($rotated) {
                            imagealphablending($rotated, false);
                            imagesavealpha($rotated, true);
                            unset($image);
                            $image = $rotated;

                            if ($deg === 90 || $deg === -90) {
                                $temp = $width;
                                $width = $height;
                                $height = $temp;
                            }
                        }
                    }
                }

                // Handle alpha for both original and WebP
                if ($mime !== 'image/jpeg') {
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }

                // Create WebP from pristine memory resource before saving as original format
                if ($mime !== 'image/webp' && function_exists('imagewebp')) {
                    $pathInfo = pathinfo($filePath);
                    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

                    @imagewebp($image, $webpPath, $quality);

                    if (file_exists($webpPath) && filesize($webpPath) === 0) {
                        grinds_force_unlink($webpPath);
                    }
                }

                // Save original image
                switch ($mime) {
                    case 'image/jpeg':
                        imagejpeg($image, $filePath, $quality);
                        break;
                    case 'image/png':
                        imagepng($image, $filePath, 7);
                        break;
                    case 'image/webp':
                        imagewebp($image, $filePath, $quality);
                        break;
                    case 'image/gif':
                        imagegif($image, $filePath);
                        break;
                    case 'image/avif':
                        if (function_exists('imageavif')) {
                            imageavif($image, $filePath, $quality);
                        }
                        break;
                }

                unset($image);
                return ['width' => $width, 'height' => $height];
            }, $imageSize, $hasImagick, $hasGd);
        } catch (Exception $e) {
            if ($e->getMessage() === "GD not available")
                return;
            throw $e;
        }
    }

    /**
     * Load GD image resource.
     */
    private static function gdLoadImage($filePath, $mime)
    {
        try {
            switch ($mime) {
                case 'image/jpeg':
                    return imagecreatefromjpeg($filePath);
                case 'image/png':
                    return imagecreatefrompng($filePath);
                case 'image/webp':
                    if (!function_exists('imagecreatefromwebp')) {
                        throw new Exception('WebP support is not available in this GD build.');
                    }
                    return imagecreatefromwebp($filePath);
                case 'image/avif':
                    if (!function_exists('imagecreatefromavif')) {
                        throw new Exception('AVIF support is not available in this GD build.');
                    }
                    return imagecreatefromavif($filePath);
                case 'image/gif':
                    return imagecreatefromgif($filePath);
            }
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'memory') || str_contains(strtolower($e->getMessage()), 'allowed memory size')) {
                throw new Exception(_t('err_image_memory_limit'));
            }
            // Re-throw specific exceptions to provide better error messages upstream.
            if (str_contains($e->getMessage(), 'support is not available')) {
                throw $e;
            }
            error_log("gdLoadImage failed: " . $e->getMessage());
        }
        return null;
    }

    private static function extractPngMetadata($filePath)
    {
        $fp = @fopen($filePath, 'rb');
        if (!$fp)
            return [];
        $sig = fread($fp, 8);
        if ($sig !== "\x89PNG\r\n\x1a\n") {
            fclose($fp);
            return [];
        }
        $metadata = [];
        while (!feof($fp)) {
            $lenBuf = fread($fp, 4);
            if (strlen($lenBuf) < 4)
                break;
            $len = unpack('N', $lenBuf)[1];
            $type = fread($fp, 4);

            if ($len < 0 || $len > 10 * 1024 * 1024) {
                break;
            }

            if ($type === 'tEXt') {
                $data = ($len > 0) ? (string)fread($fp, $len) : '';
                $parts = explode("\0", $data, 2);
                if (count($parts) === 2) {
                    $metadata[self::sanitizeUtf8($parts[0])] = self::sanitizeUtf8($parts[1]);
                }
            } elseif ($type === 'zTXt') {
                $data = ($len > 0) ? (string)fread($fp, $len) : '';
                $parts = explode("\0", $data, 2);
                if (count($parts) === 2) {
                    $key = self::sanitizeUtf8($parts[0]);
                    $compressed = substr($parts[1], 1);
                    $val = @gzuncompress($compressed, 1024 * 1024);
                    if ($val !== false)
                        $metadata[$key] = self::sanitizeUtf8($val);
                }
            } elseif ($type === 'iTXt') {
                $data = ($len > 0) ? (string)fread($fp, $len) : '';
                $parts = explode("\0", $data, 2);
                if (count($parts) >= 2) {
                    $key = self::sanitizeUtf8($parts[0]);
                    $rest = substr($data, strlen($parts[0]) + 1);
                    $lastNull = strrpos($rest, "\0");
                    if ($lastNull !== false) {
                        $val = substr($rest, $lastNull + 1);
                        if (ord($rest[0]) === 1) {
                            $val = @gzuncompress($val, 1024 * 1024);
                        }
                        if ($val !== false)
                            $metadata[$key] = self::sanitizeUtf8($val);
                    }
                }
            } elseif ($type === 'IEND') {
                break;
            } else {
                if ($len > 0)
                    fseek($fp, $len, SEEK_CUR);
            }
            fseek($fp, 4, SEEK_CUR);
        }
        fclose($fp);
        return $metadata;
    }
    private static function extractJpegMetadata($filePath)
    {
        if (!function_exists('exif_read_data'))
            return [];
        $exif = @exif_read_data($filePath, 0, true);
        $metadata = [];
        if (isset($exif['COMPUTED']['UserComment'])) {
            $metadata['parameters'] = self::sanitizeUtf8($exif['COMPUTED']['UserComment']);
        } elseif (isset($exif['COMMENT'][0])) {
            $metadata['parameters'] = self::sanitizeUtf8($exif['COMMENT'][0]);
        }
        if (isset($exif['IFD0']['ImageDescription'])) {
            $metadata['description'] = self::sanitizeUtf8($exif['IFD0']['ImageDescription']);
        }
        if (isset($exif['IFD0']['Software'])) {
            $metadata['software'] = self::sanitizeUtf8($exif['IFD0']['Software']);
        }
        if (isset($exif['IFD0']['Make'])) {
            $metadata['make'] = self::sanitizeUtf8($exif['IFD0']['Make']);
        }
        if (isset($exif['IFD0']['Model'])) {
            $metadata['model'] = self::sanitizeUtf8($exif['IFD0']['Model']);
        }
        return $metadata;
    }

    private static function extractXmpRaw($filePath)
    {
        // Read header
        $fp = @fopen($filePath, 'rb');
        if (!$fp)
            return '';
        $header = fread($fp, 262144);
        fclose($fp);

        $startTag = '<x:xmpmeta';
        $endTag = '</x:xmpmeta>';
        $startPos = strpos($header, $startTag);
        if ($startPos === false)
            return '';

        $endPos = strpos($header, $endTag, $startPos);
        if ($endPos === false)
            return '';

        $length = ($endPos + strlen($endTag)) - $startPos;
        return substr($header, $startPos, $length);
    }

    private static function sanitizeUtf8($string)
    {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }

    /**
     * Get paths of derived files (WebP, thumbnails, etc.).
     *
     * @param string $filePath Full path to the original file.
     * @return array List of full paths for derived files.
     */
    public static function getDerivativePaths($filePath)
    {
        $paths = [];
        $info = pathinfo($filePath);
        $dir = $info['dirname'];
        $filename = $info['filename'];
        $ext = strtolower($info['extension'] ?? '');

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
            return $paths;
        }

        // WebP version (if original is not webp)
        if ($ext !== 'webp') {
            $paths[] = $dir . '/' . $filename . '.webp';
        }

        // Thumbnails
        $paths[] = $dir . '/' . $filename . '_thumb.webp';
        $paths[] = $dir . '/' . $filename . '_thumb.jpg';

        return $paths;
    }

    public static function delete($relativePath)
    {
        if (empty($relativePath) || str_contains($relativePath, "\0") || str_contains($relativePath, '..'))
            return false;

        $relativePath = ltrim(preg_replace('#/+#', '/', str_replace('\\', '/', $relativePath)), '/');
        if (!str_starts_with($relativePath, 'assets/uploads/'))
            return false;

        $allowedDir = realpath(ROOT_PATH . '/assets/uploads') ?: ROOT_PATH . '/assets/uploads';
        $normAllowedDir = rtrim(str_replace('\\', '/', $allowedDir), '/') . '/';

        $fullPath = ROOT_PATH . '/' . $relativePath;

        // Targets for deletion (original file + derivatives)
        $targets = array_merge([$fullPath], self::getDerivativePaths($fullPath));

        // Process deletion with security checks
        foreach ($targets as $target) {
            $rp = realpath($target);
            if ($rp && is_file($rp)) {
                if (str_starts_with(strtolower(str_replace('\\', '/', $rp)), strtolower($normAllowedDir))) {
                    grinds_force_unlink($rp);
                }
            } elseif (is_file($target)) {
                // Fallback for environments where realpath fails
                $normTarget = str_replace('\\', '/', $target);
                $normBase = str_replace('\\', '/', ROOT_PATH . '/assets/uploads/');
                if (str_starts_with(strtolower($normTarget), strtolower($normBase))) {
                    grinds_force_unlink($target);
                }
            }
        }

        // Clean up parent directories if they are empty
        $dirPath = dirname($fullPath);
        while (is_dir($dirPath)) {
            $normDirPath = rtrim(str_replace('\\', '/', realpath($dirPath) ?: $dirPath), '/') . '/';
            // Stop if we reach the base uploads directory or go outside it
            if (strlen($normDirPath) <= strlen($normAllowedDir) || !str_starts_with(strtolower($normDirPath), strtolower($normAllowedDir))) {
                break;
            }
            if (count(array_diff(scandir($dirPath), ['.', '..'])) === 0) {
                @rmdir($dirPath);
                $dirPath = dirname($dirPath); // Move up to parent
            } else {
                break; // Not empty, stop traversing
            }
        }

        return !file_exists($fullPath);
    }

    /**
     * Sanitize SVG content.
     */
    private static function sanitizeSvg($filePath)
    {
        if (!file_exists($filePath))
            return false;

        // Read content
        $content = @file_get_contents($filePath);
        if (!$content)
            return false;

        // Prevent XML Bomb (Billion Laughs Attack) by rejecting custom entities
        if (preg_match('/<!ENTITY/i', $content)) {
            return false;
        }

        // NOTE: DOMDocument is used for robust tag/attribute sanitization.
        // A known side-effect is that it may not perfectly preserve XML namespaces
        // (e.g., `xlink:href` might be rewritten). This is a trade-off for security
        // and avoiding external dependencies. It should not affect most common SVGs.

        // Sanitize with DOMDocument
        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);

        // Load XML
        // Use options to prevent XXE and other issues
        if (!$dom->loadXML(trim($content), LIBXML_NONET | LIBXML_NOBLANKS)) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
            return false;
        }

        $xpath = new DOMXPath($dom);

        // 1. Whitelist of allowed tags
        // Based on common SVG usage.
        $allowedTags = [
            'svg',
            'g',
            'defs',
            'symbol',
            'use',
            'image',
            'path',
            'rect',
            'circle',
            'line',
            'ellipse',
            'polyline',
            'polygon',
            'text',
            'tspan',
            'desc',
            'title',
            'lineargradient',
            'radialgradient',
            'stop',
            'mask',
            'clippath',
            'filter',
            'fegaussianblur',
            'feoffset',
            'femerge',
            'femergenode',
            'style',
            'marker',
            'pattern',
            'view',
            'metadata'
        ];

        // Remove non-whitelisted tags
        $nodes = $xpath->query('//*');
        $nodesToRemove = [];
        foreach ($nodes as $node) {
            // Check localName to ignore namespace prefixes
            if (!in_array(strtolower($node->localName), $allowedTags)) {
                $nodesToRemove[] = $node;
            }
        }
        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // 2. Sanitize attributes
        // We iterate again as the DOM has changed
        $nodes = $xpath->query('//*');
        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement))
                continue;
            if (!$node->hasAttributes())
                continue;

            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $val = $attr->value;
                $decodedVal = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $valClean = strtolower(preg_replace('/[\s\x00-\x1f]/', '', $decodedVal));

                // Remove event handlers (on*)
                if (str_starts_with($name, 'on')) {
                    $attrsToRemove[] = $attr->name;
                    continue;
                }

                // Remove script execution in href/xlink:href
                if ($name === 'href' || str_contains($name, 'xlink:href')) {
                    if (str_contains($valClean, 'javascript:') || (str_contains($valClean, 'data:') && !str_starts_with($valClean, 'data:image/'))) {
                        $attrsToRemove[] = $attr->name;
                    }
                    continue;
                }

                // Check for javascript: in any attribute
                if (str_contains($valClean, 'javascript:')) {
                    $attrsToRemove[] = $attr->name;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // 3. Sanitize <style> content
        $styleNodes = $xpath->query('//*[local-name()="style"]');
        foreach ($styleNodes as $node) {
            $val = $node->nodeValue;
            $decodedVal = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $valClean = strtolower(preg_replace('/[\s\x00-\x1f]/', '', $decodedVal));

            if (str_contains($valClean, 'javascript:') || str_contains($valClean, 'expression(') || str_contains($valClean, 'vbscript:')) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return ($dom->save($filePath) !== false);
    }

    /**
     * Update media tags.
     */
    public static function updateMediaTags($pdo, $mediaId, $tags)
    {
        if (empty($mediaId))
            return;

        // Remove existing tags
        $stmtDel = $pdo->prepare("DELETE FROM media_tags WHERE media_id = ?");
        $stmtDel->execute([$mediaId]);

        if (!empty($tags)) {
            $tagIds = grinds_get_or_create_tags($pdo, $tags);
            $stmtLink = $pdo->prepare("INSERT OR IGNORE INTO media_tags (media_id, tag_id) VALUES (?, ?)");

            foreach ($tagIds as $tagId) {
                $stmtLink->execute([$mediaId, $tagId]);
            }
        }
    }

    /**
     * Get media tags.
     */
    public static function getMediaTags($pdo, $mediaId)
    {
        $stmt = $pdo->prepare("SELECT t.name FROM tags t JOIN media_tags mt ON t.id = mt.tag_id WHERE mt.media_id = ?");
        $stmt->execute([$mediaId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get tag suggestions.
     */
    public static function getTagSuggestions($pdo)
    {
        $tags = $pdo->query("SELECT name FROM tags")->fetchAll(PDO::FETCH_COLUMN);
        $cats = $pdo->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

        $all = array_unique(array_merge($tags, $cats));
        sort($all);
        return $all;
    }

    /**
     * Check memory requirements.
     */
    private static function checkMemoryRequirement($width, $height, $mime = 'image/jpeg')
    {
        $maxDimension = defined('MAX_IMAGE_DIMENSION') ? (int) constant('MAX_IMAGE_DIMENSION') : self::MAX_DIMENSION;
        if ($width > $maxDimension || $height > $maxDimension) {
            $msg = function_exists('_t') ? _t('err_image_dimension', $maxDimension) : "Image dimensions exceed the maximum allowed limit ({$maxDimension}px).";
            throw new Exception($msg, 413);
        }

        $maxPixels = defined('MAX_IMAGE_PIXELS') ? (int) constant('MAX_IMAGE_PIXELS') : self::MAX_PIXELS;
        $pixels = $width * $height;
        if ($pixels > $maxPixels) {
            throw new Exception(_t('err_pixel_flood', number_format($pixels), number_format($maxPixels)) . ' (Please resize image to < 2500px width)', 413);
        }

        // Estimate memory
        $multiplier = ($mime === 'image/png' || $mime === 'image/gif') ? 3.5 : 2.2;
        $requiredBytes = ($width * $height * 4 * $multiplier) + (64 * 1024 * 1024);
        $currentLimit = @ini_get('memory_limit');
        if ($currentLimit === '-1')
            return true;
        $limitBytes = grinds_return_bytes($currentLimit);

        if ($limitBytes !== -1 && $limitBytes < $requiredBytes) {
            $newLimit = ceil($requiredBytes / 1024 / 1024) . 'M';
            @ini_set('memory_limit', $newLimit);

            // Verify memory
            $newLimitBytes = grinds_return_bytes(@ini_get('memory_limit'));
            if ($newLimitBytes !== -1 && $newLimitBytes < $requiredBytes) {
                throw new Exception(_t('err_image_memory_limit'), 413);
            }
        }
        return true;
    }

    /**
     * Execute image operation.
     *
     * @param string $filePath
     * @param callable $imagickOp function($filePath)
     * @param callable $gdOp function($filePath, $size)
     * @param array|null $imageSize
     * @return mixed
     * @throws Exception
     */
    private static function executeImageOperation($filePath, callable $imagickOp, callable $gdOp, $imageSize = null, $hasImagick = null, $hasGd = null)
    {
        // Estimate memory
        $size = $imageSize;
        if (!$size) {
            $size = @getimagesize($filePath);
        }

        if (function_exists('grinds_set_high_load_mode')) {
            grinds_set_high_load_mode();
        }

        // Check flags if not provided (fallback)
        if ($hasImagick === null)
            $hasImagick = extension_loaded('imagick') && class_exists('Imagick');
        if ($hasGd === null)
            $hasGd = extension_loaded('gd');

        // Try ImageMagick
        if ($hasImagick) {
            try {
                return $imagickOp($filePath);
            } catch (Exception $e) {
                error_log("Imagick failed, falling back to GD: " . $e->getMessage());
            }
        }

        // Fallback to GD
        if (!$hasGd) {
            throw new Exception("GD not available");
        }



        return $gdOp($filePath, $size);
    }

    /**
     * Generate search condition.
     *
     * @param string $query Search keywords.
     * @param array $params Reference to parameters array.
     * @return string SQL condition.
     */
    public static function getSearchCondition($query, &$params)
    {
        return grinds_build_search_query($query, function ($word) use (&$params) {
            $escapedKeyword = grinds_escape_like($word);
            $params[] = "%{$escapedKeyword}%";
            $params[] = "%{$escapedKeyword}%";
            $params[] = "%{$escapedKeyword}%";
            return "(
                filename LIKE ? ESCAPE '\\'
                OR metadata LIKE ? ESCAPE '\\'
                OR EXISTS (SELECT 1 FROM media_tags mt JOIN tags t ON mt.tag_id = t.id WHERE mt.media_id = media.id AND t.name LIKE ? ESCAPE '\\')
            )";
        });
    }

    /**
     * Scan database for files.
     *
     * @param PDO $pdo
     * @param int $offset
     * @param int $limit
     * @return array ['files' => [], 'has_more' => bool, 'next_offset' => int, 'total' => int]
     */
    public static function scanDatabaseForFiles($pdo, $offset = 0, $limit = 100)
    {
        $used_files = [];

        // Scan posts
        $repo = new PostRepository($pdo);
        $posts = $repo->fetch(['status' => 'any'], $limit, $offset);

        foreach ($posts as $row) {
            if (!empty($row['thumbnail']))
                $used_files[] = self::normalizeDbPath($row['thumbnail']);
            if (!empty($row['hero_image']))
                $used_files[] = self::normalizeDbPath($row['hero_image']);

            if (!empty($row['hero_settings'])) {
                $hs = json_decode($row['hero_settings'], true);
                if (is_array($hs) && !empty($hs['mobile_image'])) {
                    $used_files[] = self::normalizeDbPath($hs['mobile_image']);
                }
            }

            self::extractPathsFromContent($row['content'], $used_files);
        }

        // Check for more
        $nextOffset = $offset + $limit;
        $totalPosts = $repo->count(['status' => 'any']);
        $hasMore = $nextOffset < $totalPosts;

        // Scan other tables
        if ($offset === 0) {
            // Scan previews (to prevent deleting files used in active previews)
            $previewDir = defined('ROOT_PATH') ? ROOT_PATH . '/data/tmp/preview' : '';
            if ($previewDir && is_dir($previewDir)) {
                $previewFiles = glob($previewDir . '/preview_*.json');
                if ($previewFiles) {
                    foreach ($previewFiles as $pf) {
                        $json = file_get_contents($pf);
                        if ($json) {
                            $pData = json_decode($json, true);
                            if (is_array($pData)) {
                                if (!empty($pData['thumbnail'])) $used_files[] = self::normalizeDbPath($pData['thumbnail']);
                                if (!empty($pData['hero_image'])) $used_files[] = self::normalizeDbPath($pData['hero_image']);

                                if (!empty($pData['hero_settings'])) {
                                    $hs = json_decode($pData['hero_settings'], true);
                                    if (is_array($hs) && !empty($hs['mobile_image'])) {
                                        $used_files[] = self::normalizeDbPath($hs['mobile_image']);
                                    }
                                }

                                if (!empty($pData['content'])) {
                                    self::extractPathsFromContent($pData['content'], $used_files);
                                }
                            }
                        }
                    }
                }
            }

            // Scan banners
            try {
                $stmt = $pdo->query("SELECT image_url FROM banners WHERE image_url IS NOT NULL AND image_url != ''");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                    $used_files[] = self::normalizeDbPath($row['image_url']);
            } catch (Exception $e) {
            }

            // Scan users
            try {
                $stmt = $pdo->query("SELECT avatar FROM users WHERE avatar IS NOT NULL AND avatar != ''");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                    $used_files[] = self::normalizeDbPath($row['avatar']);
            } catch (Exception $e) {
            }

            // Scan nav_menus
            try {
                $stmt = $pdo->query("SELECT url FROM nav_menus WHERE url IS NOT NULL AND url != ''");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                    $used_files[] = self::normalizeDbPath($row['url']);
            } catch (Exception $e) {
            }

            // Scan settings
            try {
                $settingOffset = 0;
                $settingLimit = 500;
                $stmt = $pdo->prepare("SELECT value FROM settings LIMIT ? OFFSET ?");
                do {
                    $stmt->execute([$settingLimit, $settingOffset]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) break;

                    foreach ($rows as $row) {
                        if (!empty($row['value']) && (
                            str_contains($row['value'], 'assets/uploads') ||
                            str_contains($row['value'], 'assets\/uploads') ||
                            str_contains($row['value'], '{{CMS_URL}}')
                        )) {
                            $used_files[] = self::normalizeDbPath($row['value']);
                        }
                    }
                    $settingOffset += $settingLimit;
                } while (count($rows) === $settingLimit);
            } catch (Exception $e) {
            }

            // Scan widgets
            try {
                $widgetOffset = 0;
                $widgetLimit = 500;
                $stmt = $pdo->prepare("SELECT content, settings FROM widgets LIMIT ? OFFSET ?");
                do {
                    $stmt->execute([$widgetLimit, $widgetOffset]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) break;

                    foreach ($rows as $row) {
                        $settings = json_decode($row['settings'] ?? '{}', true);
                        if (is_array($settings) && !empty($settings['image'])) {
                            $used_files[] = self::normalizeDbPath($settings['image']);
                        }
                        if (!empty($row['content'])) {
                            self::extractPathsFromContent($row['content'], $used_files);
                        }
                    }
                    $widgetOffset += $widgetLimit;
                } while (count($rows) === $widgetLimit);
            } catch (Exception $e) {
            }
        }

        // Expand used files
        $expanded_files = [];
        foreach ($used_files as $file) {
            if (empty($file))
                continue;
            $expanded_files[] = $file;

            $derivatives = self::getDerivativePaths($file);
            foreach ($derivatives as $derivative) {
                $expanded_files[] = $derivative;
            }
        }
        $used_files = $expanded_files;

        return [
            'files' => array_values(array_unique(array_filter($used_files))),
            'has_more' => $hasMore,
            'next_offset' => $nextOffset,
            'total' => $totalPosts
        ];
    }

    private static function normalizeDbPath($path)
    {
        if (empty($path))
            return '';
        $path = str_replace('\\', '/', $path);
        $path = urldecode($path);
        $path = preg_replace('/[\?#].*$/', '', $path);
        $path = trim($path);
        $path = preg_replace('#/+#', '/', $path);
        if (str_contains($path, 'assets/uploads/')) {
            $parts = explode('assets/uploads/', $path);
            if (count($parts) > 1)
                return 'assets/uploads/' . end($parts);
        }
        return ltrim($path, '/');
    }

    public static function extractPathsFromContent($content, &$used_files)
    {
        if (empty($content))
            return;

        // Use centralized extractor for attributes and JSON
        if (function_exists('grinds_extract_urls')) {
            $urls = grinds_extract_urls($content);
            foreach ($urls as $url) {
                $used_files[] = self::normalizeDbPath($url);
            }
        }
    }

    /**
     * Scan directory recursively.
     *
     * @param string $dir Directory path.
     * @param array $options Options: exclude_dirs, exclude_files, exclude_exts, include_exts, since.
     * @return array List of file paths.
     */
    public static function scanDirectory($dir, $options = [])
    {
        if (!is_dir($dir))
            return [];

        $excludeDirs = $options['exclude_dirs'] ?? ['node_modules', 'vendor', '.git'];
        $excludeFiles = $options['exclude_files'] ?? ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'config.php', '.DS_Store', 'Thumbs.db'];
        $excludeExts = $options['exclude_exts'] ?? [];
        $includeExts = $options['include_exts'] ?? [];
        $since = $options['since'] ?? null;

        // Normalize extensions
        $excludeExts = array_map('strtolower', $excludeExts);
        $includeExts = array_map('strtolower', $includeExts);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                function ($current, $key, $iterator) use ($excludeDirs, $excludeFiles) {
                    $filename = $current->getFilename();
                    // Skip hidden files
                    if (str_starts_with($filename, '.') && $filename !== '.htaccess')
                        return false;

                    if ($current->isDir()) {
                        return !in_array($filename, $excludeDirs);
                    }

                    if (in_array($filename, $excludeFiles)) {
                        return false;
                    }
                    return true;
                }
            )
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (!empty($includeExts) && !in_array($ext, $includeExts))
                    continue;
                if (in_array($ext, $excludeExts))
                    continue;
                if ($since !== null && $file->getMTime() <= $since)
                    continue;
                yield str_replace('\\', '/', $file->getPathname());
            }
        }
    }

    /**
     * Check file usage in bulk (for deletion safety).
     *
     * @param PDO $pdo
     * @param array $filePaths
     * @return array Map of filePath => usageType
     */
    public static function getBulkFileUsage($pdo, $filePaths)
    {
        $usageMap = [];
        if (empty($filePaths)) {
            return $usageMap;
        }

        // Chunk to avoid SQLite parameter limit (999)
        $chunks = array_chunk($filePaths, 100);

        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            // 1. Check Exact Matches (Fast)
            // Use REPLACE to strip {{CMS_URL}}/ placeholder for correct matching against relative file paths.
            $sql = "SELECT REPLACE(thumbnail, '{{CMS_URL}}/', '') as path, 'content' as type FROM posts WHERE REPLACE(thumbnail, '{{CMS_URL}}/', '') IN ($placeholders)
                UNION
                SELECT REPLACE(hero_image, '{{CMS_URL}}/', '') as path, 'content' as type FROM posts WHERE REPLACE(hero_image, '{{CMS_URL}}/', '') IN ($placeholders)
                UNION
                SELECT REPLACE(image_url, '{{CMS_URL}}/', '') as path, 'banner' as type FROM banners WHERE REPLACE(image_url, '{{CMS_URL}}/', '') IN ($placeholders)
                UNION
                SELECT REPLACE(avatar, '{{CMS_URL}}/', '') as path, 'avatar' as type FROM users WHERE REPLACE(avatar, '{{CMS_URL}}/', '') IN ($placeholders)
                UNION
                SELECT value as path, 'settings' as type FROM settings WHERE value IN ($placeholders)
                UNION
                SELECT REPLACE(url, '{{CMS_URL}}/', '') as path, 'menu' as type FROM nav_menus WHERE REPLACE(url, '{{CMS_URL}}/', '') IN ($placeholders)"; // Fuzzy search will catch settings with placeholders

            // Prepare params (repeated for each IN clause)
            $params = array_merge($chunk, $chunk, $chunk, $chunk, $chunk, $chunk);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Path can be null or empty if REPLACE results in an empty string from a placeholder-only value
                if (!empty($row['path'])) {
                    $usageMap[$row['path']] = $row['type'];
                }
            }

            // 2. Check Fuzzy Matches (Slower but necessary for embedded content)
            $remainingInChunk = array_diff($chunk, array_keys($usageMap));
            if (!empty($remainingInChunk)) {
                // Helper for fuzzy search
                $fuzzySearch = function ($table, $columns, $usageType) use ($pdo, &$usageMap, $remainingInChunk) {
                    $remaining = array_diff($remainingInChunk, array_keys($usageMap));
                    if (empty($remaining))
                        return;

                    $likeConditions = [];
                    $likeParams = [];
                    foreach ($remaining as $path) {
                        $escapedPath = str_replace('/', '\\/', $path);
                        foreach ($columns as $col) {
                            $pathForLike = grinds_escape_like($path);
                            $likeConditions[] = "$col LIKE ? ESCAPE '\\'";
                            $likeParams[] = '%' . $pathForLike . '%';
                            if ($path !== $escapedPath) {
                                $escapedPathForLike = grinds_escape_like($escapedPath);
                                $likeConditions[] = "$col LIKE ? ESCAPE '\\'";
                                $likeParams[] = '%' . $escapedPathForLike . '%';
                            }
                        }
                    }

                    if (empty($likeConditions))
                        return;

                    $sql = "SELECT " . implode(', ', $columns) . " FROM $table WHERE " . implode(' OR ', $likeConditions);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($likeParams);

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $content = implode(' ', $row);
                        foreach ($remaining as $path) {
                            if (isset($usageMap[$path]))
                                continue;
                            if (self::isPathInContent($content, $path)) {
                                $usageMap[$path] = $usageType;
                                continue;
                            }

                            $escapedPath = str_replace('/', '\\/', $path);
                            if ($path !== $escapedPath && self::isPathInContent($content, $escapedPath)) {
                                $usageMap[$path] = $usageType;
                            }
                        }
                    }
                };

                // Execute fuzzy searches for relevant tables
                $fuzzySearch('posts', ['content', 'hero_settings'], 'content');
                $fuzzySearch('widgets', ['content', 'settings'], 'widget');
                $fuzzySearch('settings', ['value'], 'settings');
            }
        }

        return $usageMap;
    }

    /**
     * Check if path exists in content with boundary check.
     */
    private static function isPathInContent($content, $path)
    {
        if (empty($path)) {
            return false;
        }

        $escapedPath = preg_quote($path, '/');
        $pattern = '/(?<![a-zA-Z0-9\.\-\_\/])' . $escapedPath . '(?![a-zA-Z0-9\.\-\_\/])/u';

        return preg_match($pattern, $content) === 1;
    }
}
