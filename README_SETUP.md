# FoodShare Setup Guide

## Database Setup

### Step 1: Create Database and Tables
1. Open your web browser
2. Go to: `http://localhost:8000/setup_database_fixed.php`
3. This will create the database and all required tables
4. **Fixed**: Email field now properly handled in registration

### Step 2: Test Database Connection
1. Go to: `http://localhost:8000/database_test.html`
2. This will test if the database is working properly

### Step 3: Test Registration
1. Go to: `http://localhost:8000/test_registration.php`
2. This will verify the registration system works with email

### Step 4: Test Setup Fix
1. Go to: `http://localhost:8000/test_setup.php`
2. This will verify the database setup is working correctly

### Step 3: Test Food Posting Functionality
1. Register a new donor account at: `http://localhost:8000/register.html`
2. Login as a donor at: `http://localhost:8000/login.html`
3. Go to: `http://localhost:8000/donor_post_food.php` to post food
4. Check your dashboard at: `http://localhost:8000/donor_dashboard.php`

### Step 4: Test Food Claiming Functionality
1. Register a new receiver account at: `http://localhost:8000/register.html`
2. Login as a receiver at: `http://localhost:8000/login.html`
3. Go to: `http://localhost:8000/receiver_claim_food.php` to claim food
4. Check your dashboard at: `http://localhost:8000/receiver_dashboard.php`

## Database Schema

The following tables are created:
- **users**: User accounts (donors, receivers, admins)
- **food_posts**: Food items posted by donors
- **pickups**: Food claims by receivers
- **notifications**: System notifications

## Sample Login Credentials

After setup, you can use these test accounts:
- **Donor**: username: `donor1`, password: `password`
- **Receiver**: username: `receiver1`, password: `password`
- **Admin**: username: `admin`, password: `password`

## Features

### Donor Features:
- Post food items with images
- Set expiration dates and pickup locations
- View posted food items
- Manage pickup requests
- Receive notifications

### Receiver Features:
- Browse available food items
- Claim food items
- View claimed food history
- Receive notifications
- Manage pickup status

### Admin Features:
- User management
- System monitoring
- Content moderation

## Troubleshooting

### Database Connection Issues:
1. Make sure MySQL is running
2. Check database credentials in `db.php`
3. Run the setup script again

### File Upload Issues:
1. Check if `uploads` directory exists and is writable
2. Verify file permissions (755)
3. Check PHP file upload settings

### Login Issues:
1. Verify user exists in database
2. Check password hashing
3. Clear browser cache and cookies

## Server Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- File upload permissions

## Support

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check PHP error logs
3. Verify database connectivity
4. Test with sample data first
