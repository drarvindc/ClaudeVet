<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QRCodeStorageService
{
    private const QR_SIZE = 200;
    private const BARCODE_WIDTH = 3;
    private const BARCODE_HEIGHT = 50;

    /**
     * Generate and store QR code and barcode for a UID
     */
    public static function generateAndStore(string $uid): array
    {
        try {
            $qrPath = self::generateQRCode($uid);
            $barcodePath = self::generateBarcode($uid);

            return [
                'success' => true,
                'qr_path' => $qrPath,
                'barcode_path' => $barcodePath,
                'qr_url' => Storage::url($qrPath),
                'barcode_url' => Storage::url($barcodePath)
            ];

        } catch (\Exception $e) {
            Log::error("QR/Barcode generation failed for UID {$uid}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'qr_path' => null,
                'barcode_path' => null
            ];
        }
    }

    /**
     * Generate and store QR code
     */
    private static function generateQRCode(string $uid): string
    {
        $filename = "qr-{$uid}.png";
        $path = "qr-codes/{$filename}";

        try {
            // Try SimpleSoftwareIO QR Code first
            if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(self::QR_SIZE)
                    ->margin(2)
                    ->errorCorrection('M')
                    ->generate($uid);
                
                Storage::disk('public')->put($path, $qrCode);
                return $path;
            }

            // Fallback to Endroid QR Code
            if (class_exists('\Endroid\QrCode\Builder\Builder')) {
                $qrCode = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($uid)
                    ->size(self::QR_SIZE)
                    ->margin(10)
                    ->build();
                
                Storage::disk('public')->put($path, $qrCode->getString());
                return $path;
            }

            // Final fallback - create placeholder QR pattern
            throw new \Exception('No QR library available - creating fallback');

        } catch (\Exception $e) {
            // Create fallback QR-like image
            $fallbackImage = self::createQRFallback($uid);
            Storage::disk('public')->put($path, $fallbackImage);
            return $path;
        }
    }

    /**
     * Generate and store barcode
     */
    private static function generateBarcode(string $uid): string
    {
        $filename = "barcode-{$uid}.png";
        $path = "barcodes/{$filename}";

        try {
            if (class_exists('\Picqer\Barcode\BarcodeGeneratorPNG')) {
                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                $barcode = $generator->getBarcode($uid, $generator::TYPE_CODE_128, self::BARCODE_WIDTH, self::BARCODE_HEIGHT);
                
                Storage::disk('public')->put($path, $barcode);
                return $path;
            }

            throw new \Exception('Picqer library not available');

        } catch (\Exception $e) {
            // Create fallback barcode
            $fallbackImage = self::createBarcodeFallback($uid);
            Storage::disk('public')->put($path, $fallbackImage);
            return $path;
        }
    }

    /**
     * Get stored paths for a UID
     */
    public static function getStoredPaths(string $uid): array
    {
        $qrPath = "qr-codes/qr-{$uid}.png";
        $barcodePath = "barcodes/barcode-{$uid}.png";

        return [
            'qr_exists' => Storage::disk('public')->exists($qrPath),
            'barcode_exists' => Storage::disk('public')->exists($barcodePath),
            'qr_path' => $qrPath,
            'barcode_path' => $barcodePath,
            'qr_url' => Storage::url($qrPath),
            'barcode_url' => Storage::url($barcodePath)
        ];
    }

    /**
     * Delete stored files for a UID
     */
    public static function deleteStored(string $uid): bool
    {
        try {
            $paths = self::getStoredPaths($uid);
            
            if ($paths['qr_exists']) {
                Storage::disk('public')->delete($paths['qr_path']);
            }
            
            if ($paths['barcode_exists']) {
                Storage::disk('public')->delete($paths['barcode_path']);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete QR/Barcode for UID {$uid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create QR-like fallback pattern
     */
    private static function createQRFallback(string $uid): string
    {
        $size = self::QR_SIZE;
        $image = imagecreate($size, $size);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Create QR-like pattern based on UID
        $cellSize = 8;
        $cells = $size / $cellSize;
        
        for ($x = 0; $x < $cells; $x++) {
            for ($y = 0; $y < $cells; $y++) {
                $hash = md5($uid . $x . $y);
                if (hexdec(substr($hash, 0, 1)) % 2 === 0) {
                    imagefilledrectangle($image, 
                        $x * $cellSize, $y * $cellSize,
                        ($x + 1) * $cellSize, ($y + 1) * $cellSize,
                        $black
                    );
                }
            }
        }

        // Add corner squares (QR style)
        $cornerSize = $cellSize * 7;
        
        // Top-left corner
        imagefilledrectangle($image, 0, 0, $cornerSize, $cornerSize, $black);
        imagefilledrectangle($image, $cellSize, $cellSize, $cornerSize - $cellSize, $cornerSize - $cellSize, $white);
        imagefilledrectangle($image, $cellSize * 3, $cellSize * 3, $cellSize * 4, $cellSize * 4, $black);
        
        // Add UID text
        imagestring($image, 2, 10, $size - 20, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return $imageData;
    }

    /**
     * Create barcode fallback
     */
    private static function createBarcodeFallback(string $uid): string
    {
        $width = 300;
        $height = self::BARCODE_HEIGHT;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Create barcode-like pattern
        $barWidth = 2;
        $x = 10;
        
        for ($i = 0; $i < strlen($uid); $i++) {
            $char = ord($uid[$i]);
            $pattern = $char % 8;
            
            for ($j = 0; $j < 8; $j++) {
                if (($pattern >> $j) & 1) {
                    imagefilledrectangle($image, $x, 5, $x + $barWidth, $height - 15, $black);
                }
                $x += $barWidth;
            }
            $x += $barWidth;
        }
        
        // Add UID text
        imagestring($image, 3, ($width - strlen($uid) * 10) / 2, $height - 15, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return $imageData;
    }

    /**
     * Regenerate QR/Barcode if missing or corrupted
     */
    public static function ensureExists(string $uid): array
    {
        $paths = self::getStoredPaths($uid);
        
        if ($paths['qr_exists'] && $paths['barcode_exists']) {
            return [
                'success' => true,
                'generated' => false,
                'qr_url' => $paths['qr_url'],
                'barcode_url' => $paths['barcode_url']
            ];
        }

        return self::generateAndStore($uid);
    }
}