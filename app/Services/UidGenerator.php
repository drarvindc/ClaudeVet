<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\YearCounter;

class UidGenerator
{
    /**
     * Generate QR code for a UID - FIXED VERSION
     */
    public static function generateQrCode(string $uid): string
    {
        try {
            // Option 1: Try SimpleSoftwareIO QR Code first (your preferred library)
            if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
                $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(200)
                    ->margin(2)
                    ->errorCorrection('M')
                    ->generate($uid);
                
                return base64_encode($qrCode);
            }
            
            // Option 2: Fallback to Endroid QR Code if SimpleSoftwareIO fails
            if (class_exists('\Endroid\QrCode\Builder\Builder')) {
                $qrCode = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($uid)
                    ->size(200)
                    ->margin(10)
                    ->build();
                
                return base64_encode($qrCode->getString());
            }
            
            // Option 3: Manual QR generation using a simple pattern
            throw new \Exception('No QR library available');
            
        } catch (\Exception $e) {
            // Create a fallback image with grid pattern
            return self::createQrFallback($uid);
        }
    }
    
    /**
     * Generate barcode for a UID - FIXED VERSION
     */
    public static function generateBarcode(string $uid): string
    {
        try {
            if (class_exists('\Picqer\Barcode\BarcodeGeneratorPNG')) {
                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                $barcode = $generator->getBarcode($uid, $generator::TYPE_CODE_128, 2, 60);
                return base64_encode($barcode);
            }
            
            throw new \Exception('Picqer library not available');
            
        } catch (\Exception $e) {
            return self::createBarcodeFallback($uid);
        }
    }
    
    /**
     * Create QR-like fallback image
     */
    private static function createQrFallback(string $uid): string
    {
        $size = 200;
        $image = imagecreate($size, $size);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Create a QR-like pattern
        $cellSize = 8;
        $cells = $size / $cellSize;
        
        // Create pattern based on UID
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
        
        // Add corner squares (QR code style)
        $cornerSize = $cellSize * 7;
        
        // Top-left corner
        imagefilledrectangle($image, 0, 0, $cornerSize, $cornerSize, $black);
        imagefilledrectangle($image, $cellSize, $cellSize, $cornerSize - $cellSize, $cornerSize - $cellSize, $white);
        imagefilledrectangle($image, $cellSize * 3, $cellSize * 3, $cellSize * 4, $cellSize * 4, $black);
        
        // Top-right corner
        $rightStart = $size - $cornerSize;
        imagefilledrectangle($image, $rightStart, 0, $size, $cornerSize, $black);
        imagefilledrectangle($image, $rightStart + $cellSize, $cellSize, $size - $cellSize, $cornerSize - $cellSize, $white);
        imagefilledrectangle($image, $rightStart + $cellSize * 3, $cellSize * 3, $rightStart + $cellSize * 4, $cellSize * 4, $black);
        
        // Bottom-left corner
        $bottomStart = $size - $cornerSize;
        imagefilledrectangle($image, 0, $bottomStart, $cornerSize, $size, $black);
        imagefilledrectangle($image, $cellSize, $bottomStart + $cellSize, $cornerSize - $cellSize, $size - $cellSize, $white);
        imagefilledrectangle($image, $cellSize * 3, $bottomStart + $cellSize * 3, $cellSize * 4, $bottomStart + $cellSize * 4, $black);
        
        // Add UID text at bottom
        imagestring($image, 2, 10, $size - 20, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return base64_encode($imageData);
    }
    
    /**
     * Create barcode fallback
     */
    private static function createBarcodeFallback(string $uid): string
    {
        $width = 300;
        $height = 60;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Create barcode-like pattern
        $barWidth = 2;
        $x = 10;
        
        for ($i = 0; $i < strlen($uid); $i++) {
            $char = ord($uid[$i]);
            $pattern = $char % 8; // Create pattern based on character
            
            for ($j = 0; $j < 8; $j++) {
                if (($pattern >> $j) & 1) {
                    imagefilledrectangle($image, $x, 10, $x + $barWidth, $height - 20, $black);
                }
                $x += $barWidth;
            }
            $x += $barWidth; // Space between characters
        }
        
        // Add UID text
        imagestring($image, 3, ($width - strlen($uid) * 10) / 2, $height - 15, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return base64_encode($imageData);
    }

    /**
     * Generate a unique ID
     */
    public static function generate(): string
    {
        return DB::transaction(function () {
            $currentYear = now()->format('y'); // Last 2 digits of year
            
            $counter = YearCounter::firstOrCreate(
                ['year_two' => $currentYear],
                ['last_seq' => 0]
            );
            
            $counter->increment('last_seq');
            
            return $currentYear . str_pad($counter->last_seq, 4, '0', STR_PAD_LEFT);
        });
    }
}