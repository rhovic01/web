# CICT Resource Management System (CICT-RMS)

## Overview

CICT-RMS is a comprehensive resource management system designed for educational institutions to manage inventory, track borrowing and returning of items, and maintain transaction history. The system supports multiple user roles (admin and officer) with different levels of access and functionality.

## Features

- User Authentication and Authorization (Admin/Officer roles)
- Inventory Management
- Item Borrowing and Returning System
- Transaction History Tracking
- Student Information Management
- Report Generation (PDF and Excel)
- Real-time Item Availability Updates

## Prerequisites

- XAMPP (with PHP 7.4 or higher)
- MySQL 5.7 or higher
- Web Browser (Chrome, Firefox, or Edge recommended)
- Composer (PHP package manager)

## Installation

1. **Clone/Download the Repository**

   - Place the project files in your XAMPP's htdocs directory
   - Default path: `C:/xampp/htdocs/CICT_MOCK/`

2. **Install Composer (If not already installed)**

   - Download the Composer installer from [https://getcomposer.org/download/](https://getcomposer.org/download/)
   - Run the installer (Composer-Setup.exe for Windows)
   - During installation:
     - Select PHP from XAMPP (usually `C:\xampp\php\php.exe`)
     - Allow the installer to set up the PATH environment variable
   - Verify installation by opening command prompt and typing:
     ```bash
     composer --version
     ```

3. **Install Project Dependencies**

   - Open command prompt or terminal
   - Navigate to your project directory:
     ```bash
     cd C:\xampp\htdocs\CICT_MOCK
     ```
   - Install required packages:
     ```bash
     composer install
     ```
   - This will install:
     - PHPSpreadsheet (^1.29) for Excel file handling
     - Any other dependencies specified in composer.json

   If you encounter any errors:

   - Make sure PHP is in your system PATH
   - Try running as administrator
   - Check your PHP version (required 7.4 or higher)
   - Run `composer update` to get the latest package versions

4. **Database Setup**

   - Start XAMPP Control Panel
   - Start Apache and MySQL services
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `cict_rms`
   - Import the `databases.sql` file to set up the required tables

5. **Configure Database Connection**

   - Open `db_connect.php`
   - Verify the database credentials match your local setup
   - Default configuration:
     ```php
     hostname: "localhost"
     username: "root"
     password: ""
     database: "cict_rms"
     ```

6. **Verify Installation**
   - Check that all required files are present
   - Ensure the `vendor` directory was created
   - Verify `composer.lock` file exists
   - Test database connection
   - Check file permissions (especially for export features)

## Usage

1. **Accessing the System**

   - Start XAMPP (Apache and MySQL services)
   - Open your web browser
   - Navigate to: `http://localhost/CICT_MOCK/`

2. **User Roles**

   ### Admin

   - Manage users (create, update, deactivate)
   - Manage inventory
   - View all transaction history
   - Generate reports
   - Access dashboard analytics

   ### Officer

   - Process item borrowing
   - Handle item returns
   - View transaction history
   - Update item availability
   - Generate basic reports

3. **Key Features**

   ### Inventory Management

   - Navigate to "Manage Inventory"
   - Add new items
   - Update item quantities
   - Mark items as available/unavailable

   ### Borrowing Process

   1. Go to "Borrow Item"
   2. Enter student details
   3. Select items and quantities
   4. Due date is the same
   5. Confirm transaction

   **Important Borrowing Rules:**

   1. **Time Restrictions**

      - Borrowing is only allowed between 7:00 AM and 5:00 PM
      - No borrowing allowed before 7:00 AM or after 5:00 PM
      - All borrowed items are due by 5:00 PM on the same day

   2. **Student Information Rules**
      - Each Student ID must be unique and can only be associated with one name
      - Each Student Name must be unique and can only be associated with one ID
      - System prevents:
        - Using same ID with different names
        - Using same name with different IDs
      - This ensures accurate tracking and prevents identity confusion

   ### Returning Process

   1. Access "Return Item"
   2. Search for borrowed item
   3. Verify return condition
   4. Process return

   **Due Date and Overdue Rules:**

   - All borrowed items are due by 5:00 PM on the same day
   - Items returned after 5:00 PM will be marked as "Overdue"
   - System automatically tracks and displays overdue status
   - Overdue items are highlighted in the transaction history
   - Officers can monitor overdue items through the dashboard

   ### Reports

   - Export transaction history to Excel
   - Generate PDF reports
   - Filter by date range and transaction type

## Security Features

- Password hashing
- Session management
- Role-based access control
- Input validation
- SQL injection prevention

## Troubleshooting

1. **Database Connection Issues**

   - Verify XAMPP services are running
   - Check database credentials in `db_connect.php`
   - Ensure database and tables exist

2. **Access Denied Errors**

   - Verify user role permissions
   - Check if user account is active
   - Clear browser cache and cookies

3. **File Export Issues**
   - Ensure proper write permissions
   - Check PHP extensions (php_xlsx, php_pdf)

## Support

For technical support or questions, please contact your system administrator.

## License

This project is proprietary software. All rights reserved.
