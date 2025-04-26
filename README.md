# AZ Delivery - Food Delivery Service

A modern food delivery platform built with PHP and MySQL.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/XAMPP/WAMP server

## Installation

1. Clone this repository to your local machine:
   ```bash
   git clone https://github.com/yourusername/az-delivery.git
   ```

2. Import the database:
   - Start your MySQL server
   - Create a new database named `az_delivery`
   - Import the `database/az_delivery.sql` file

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials according to your local setup

4. Start your local server:
   - If using XAMPP: Place the project in `htdocs` directory
   - If using WAMP: Place the project in `www` directory
   - Access the website through: `http://localhost/az-delivery`

## Project Structure

```
az-delivery/
├── assets/           # Static files (CSS, JS, images)
├── config/           # Configuration files
├── controllers/      # PHP controller files
├── database/         # Database SQL file
├── includes/         # Reusable PHP components
├── models/           # PHP model files
├── uploads/          # User uploaded content
└── views/           # PHP view files
```

## Features

- Modern responsive design using Bootstrap 5
- User authentication (clients, delivery personnel, admin)
- Restaurant browsing and food ordering
- Order tracking system
- Admin dashboard for management
- Delivery personnel interface

## Security

- Password hashing
- SQL injection protection
- XSS protection
- CSRF protection
"# Delivery" 
