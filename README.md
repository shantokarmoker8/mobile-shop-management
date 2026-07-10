# Mobile Shop & Service Management System

A simple Shop & Service Management web app for mobile phone shops, built with **PHP, MySQL, and Bootstrap 5** (no framework required).

## Features

- Dashboard with cash, stock, sales, purchase, expense, profit, and due summary
- Product & category management
- Purchase management with supplier tracking
- Sales / POS with due sales and profit calculation
- Sales return
- Service jobs (repair tracking with parts and charges)
- Customer, Supplier, and Staff management with permissions
- Cash management (deposit, withdraw, history)
- Expense tracking
- Reports (sales, purchase, service, expense, profit, cash, stock, due)
- Settings (shop info, print size, backup/restore, delete all data)
- Print-ready invoices for sales, purchase, and service

## Tech Stack

- **Frontend:** HTML, CSS, Bootstrap 5, Bootstrap Icons, JavaScript, Chart.js, SweetAlert2
- **Backend:** PHP 8+, MySQL

## Installation

1. Clone or download this repository into your server's root folder (e.g. `htdocs/`).
2. Create a database by importing `database/shop_management.sql` in phpMyAdmin.
3. Open `config/db.php` and set your database username/password.
4. Place `logo.png`, `loading.gif`, and `fevicon.png` inside the `assets/` folder.
5. Start Apache and MySQL, then open the project in your browser.

## Default Login

- **Username:** admin
- **Password:** admin123

Change this after your first login from **My Profile**.

## Note

Passwords are stored in plain text (no hashing) as per project requirements. Not recommended for public/production use without switching to `password_hash()` / `password_verify()`.

## Developer

**Shanto Karmoker**
Email: shantokarmoker8@gmail.com
GitHub: https://github.com/shantokarmoker8
