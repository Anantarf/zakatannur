# ZakatAnNur (Professional Zakat Management System)

[![Laravel](https://img.shields.io/badge/Laravel-v9.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-v8.x-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**ZakatAnNur** is a robust, lightweight Zakat and Infaq management system designed for mosques and social organizations. It provides a seamless bridge between internal operational recording and public transparency.

## 🚀 Key Features

- **Multi-Category Transaction Support**: Handle Zakat Fitrah, Fidyah, Zakat Mal, and Infaq Shodaqoh in one unified interface.
- **Modern "Lean Service, Fat Model" Architecture**: Business logic is centralized within Eloquent models using modern Attribute accessors (PHP 8.2+) for maximum reusability and clean views.
- **Smart Receipt System**: Instant PDF receipt generation with professional overlays and dynamic labeling.
- **Public Transparency Dashboard**: Real-time summary charts and data API for public accountability.
- **Advanced Audit Logging**: Tracking every transaction lifecycle (Created, Updated, Deleted, Restored) with detailed metadata.
- **Database-Agnostic Design**: Optimized for both MySQL (Production) and SQLite (Testing/Dev).
- **Responsive Internal UI**: Interactive batch-transaction powered by Alpine.js and Tailwind CSS.

## 🛠 Tech Stack

- **Framework**: [Laravel 9.x](https://laravel.com)
- **Frontend**: [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
- **Database**: MySQL / SQLite
- **PDF Engine**: [FPDI](https://www.setasign.com/products/fpdi/about/) & [TCPDF](https://tcpdf.org/)
- **Charts**: [Chart.js](https://www.chartjs.org/)

## 📦 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- Composer
- Node.js & NPM

### Step-by-Step Guide

1. **Clone the repository**
   ```bash
   git clone https://github.com/Anantarf/zakatannur.git
   cd zakatannur
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 🔐 Default Credentials (via Seeder)
- **Username**: `superadmin`
- **Password**: `password`
*(Note: Please change these credentials immediately after deployment)*

## 🧪 Testing
The system includes a comprehensive test suite to ensure business logic integrity:
```bash
php artisan test
```

## 📜 Business Rules
- **Transaction Logic**: Automated computation of Zakat Fitrah (Jiwa) and Fidyah (Hari) based on annual settings.
- **Concurrency**: Implements `Cache::lock` to prevent race conditions during transaction number generation.
- **Soft Deletes**: Transactions are safeguarded via soft deletes with a dedicated Trash Bin for recovery.

---
Built with ❤️ for religious and social transparency.
