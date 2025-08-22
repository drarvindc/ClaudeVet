<?php

namespace App\Http\Controllers;

use App\Services\UidGenerator;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function generateQr($uid)
    {
        $qrCode = UidGenerator::generateQrCode($uid);
        return response($qrCode)
            ->header('Content-Type', 'image/png');
    }
    
    public function generateBarcode($uid)
    {
        $barcode = UidGenerator::generateBarcode($uid);
        return response($barcode)
            ->header('Content-Type', 'image/png');
    }
}