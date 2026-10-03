# Hostinger Deployment - Laravel 13 + PHP 8.4

## Server Structure
```
/home/u_______/
├── easylogics-laravel/        ← STEP 1: Upload laravel-app.zip here
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env                   ← STEP 3: Copy from .env.production
│   └── ...
└── public_html/
    ├── (CakePHP files)        ← DO NOT TOUCH
    └── new/                   ← STEP 2: Upload public files here
        ├── index.php          ← Use deploy/public_new_index.php
        ├── .htaccess          ← From public/.htaccess
        ├── favicon.ico
        ├── robots.txt
        └── files/
```

## CloudPanel (live: societynew.in) layout
```
/home/societynew1/htdocs/
├── easylogics-laravel/        ← Laravel project (app, vendor, .env ...) – NOT web-accessible
└── societynew.in/             ← site root (CakePHP)
    └── new/                   ← index.php (= deploy/public_new_index.php), .htaccess, files/ ...
```
`index.php` finds `easylogics-laravel` automatically (sibling of the site root,
or inside it); if it is missing it shows a clear error instead of a blank 500.

## Steps

### STEP 1: Upload Laravel project
- ZIP the entire easylogics-laravel folder (WITHOUT public/ and deploy/)
- Upload to /home/u_______/ via File Manager
- Extract it as "easylogics-laravel"

### STEP 2: Upload public files to public_html/new/
- Create folder: public_html/new/
- Upload deploy/public_new_index.php as public_html/new/index.php
- Upload public/.htaccess to public_html/new/.htaccess
- Upload public/favicon.ico to public_html/new/
- Upload public/robots.txt to public_html/new/
- Upload public/files/ folder to public_html/new/files/

### STEP 3: Create .env
- Copy .env.production as .env in easylogics-laravel/
- DB credentials are already set:
  - DB_DATABASE=societynew
  - DB_USERNAME=societynew

### STEP 4: Set permissions (SSH or File Manager)
- storage/ → 775
- bootstrap/cache/ → 775

### STEP 5: Set PHP version
- hPanel → Advanced → PHP Configuration
- Set PHP 8.4 for the domain

### STEP 6: Test
- Open https://societynew.in/new
