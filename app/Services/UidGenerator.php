<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\YearCounter;

class UidGenerator
{
    /**
     * Generate a new unique ID with checksum
     * Format: YY####C where C is checksum digit
     */
    public static function generate(): string
    {
        $yearTwo = date('y');
        
        return DB::transaction(function () use ($yearTwo) {
            // Lock the counter row for this year
            $counter = YearCounter::where('year_two', $yearTwo)->lockForUpdate()->first();
            
            if (!$counter) {
                $counter = YearCounter::create([
                    'year_two' => $yearTwo,
                    'last_seq' => 0
                ]);
            }
            
            // Increment the sequence
            $counter->last_seq++;
            $counter->save();
            
            // Format: YY#### (6 digits)
            $baseUid = $yearTwo . str_pad($counter->last_seq, 4, '0', STR_PAD_LEFT);
            
            // Calculate checksum using Luhn algorithm
            $checksum = self::calculateChecksum($baseUid);
            
            return $baseUid . $checksum;
        });
    }
    
    /**
     * Validate a UID with checksum
     */
    public static function validate(string $uid): bool
    {
        // UID should be 7 characters (YY####C)
        if (strlen($uid) !== 7) {
            return false;
        }
        
        $baseUid = substr($uid, 0, 6);
        $checksum = substr($uid, 6, 1);
        
        return self::calculateChecksum($baseUid) === $checksum;
    }
    
    /**
     * Extract base UID without checksum
     */
    public static function extractBase(string $uid): string
    {
        if (strlen($uid) === 7) {
            return substr($uid, 0, 6);
        }
        return $uid;
    }
    
    /**
     * Calculate checksum digit using Luhn algorithm
     */
    private static function calculateChecksum(string $number): string
    {
        $sum = 0;
        $length = strlen($number);
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return (string) ((10 - ($sum % 10)) % 10);
    }
    
    /**
     * Generate QR code for a UID
     */
    public static function generateQrCode(string $uid): string
    {
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($uid);
            
        return base64_encode($qrCode);
    }
    
    /**
     * Generate barcode for a UID
     */
    public static function generateBarcode(string $uid): string
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($uid, $generator::TYPE_CODE_128, 2, 60);
        
        return base64_encode($barcode);
    }
}