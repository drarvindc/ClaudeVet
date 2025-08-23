# VetRX Mobile - Veterinary Clinic Android App

A native Android application for veterinary clinic staff to quickly upload patient documents by scanning QR codes/barcodes and capturing photos.

## Features

- QR/Barcode scanning for patient identification
- Photo capture and gallery selection
- Document upload with categorization
- Real-time visit management
- Today's attachments display

## Setup

1. Open project in Android Studio
2. Sync Gradle dependencies
3. Run on device (camera functionality requires hardware)

## API Integration

Connects to Laravel backend at `https://app.vetrx.in/api/mobile/`

- `POST /session` - Open visit
- `POST /files` - Upload documents
- `GET /today` - Get today's visits

## Document Types

- prescription, photo, lab, xray, usg, certificate, report

## Requirements

- Android 7.0+ (API 24)
- Camera permissions
- Network access
