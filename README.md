<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Activity Tracking System

A modern, professional web application designed for support teams to manage and track daily activities with comprehensive reporting and collaboration features.

## About the Application

The Activity Tracking System is built with Laravel and provides a complete solution for support team activity management. The application features:

-   **Modern UI Design**: Professional interface with clean layouts and intuitive navigation
-   **Activity Management**: Complete CRUD operations for tracking daily activities
-   **Team Collaboration**: Status updates, handovers, and real-time communication
-   **Comprehensive Reporting**: Analytics, insights, and customizable reports with multiple export formats
-   **User Management**: Role-based access control and user profiles
-   **Responsive Design**: Mobile-first approach with cross-device compatibility

## Technology Stack

-   **Backend**: Laravel 9.52.20 (PHP 8.0+)
-   **Frontend**: Tailwind CSS + Alpine.js
-   **Database**: MySQL 8.0+
-   **Template Engine**: Laravel Blade components
-   **Icons**: Heroicons
-   **Fonts**: Inter font family

## Documentation

### 📚 Complete Documentation

-   **[PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md)** - Comprehensive project architecture and features
-   **[INSTALLATION.md](INSTALLATION.md)** - Detailed installation and deployment guide
-   **[TESTING_FLOW.md](TESTING_FLOW.md)** - Complete application testing documentation
-   **[design.md](design.md)** - UI/UX design specifications and components
-   **[tasks.md](tasks.md)** - Implementation progress and task tracking

### 🎯 Key Features Documentation

-   **Modern UI Design**: Professional interface following contemporary design principles
-   **Activity Management**: Complete CRUD operations with status tracking
-   **Team Collaboration**: User assignment and activity handovers
-   **Advanced Reporting**: Multiple export formats (CSV, PDF, Excel)
-   **Security**: Role-based access control and audit logging
-   **Performance**: Optimized for speed and scalability

### 📸 Visual Documentation

The application includes comprehensive visual documentation with screenshots of all major pages:

-   Welcome page and authentication flow
-   Dashboard with activity overview
-   Activity management and detailed views
-   Reporting and export functionality
-   User profile and navigation

## Architecture

### Design System

-   **Colors**: Blue primary (#3B82F6) with semantic color palette
-   **Typography**: Inter font family with hierarchical scaling
-   **Components**: Reusable Blade components with Tailwind CSS
-   **Responsive**: Mobile-first design with breakpoint system
-   **Accessibility**: WCAG 2.1 compliant with proper ARIA labels

### Security Features

-   CSRF protection on all forms
-   Role-based access control
-   Secure password hashing
-   Session management with timeout
-   Comprehensive audit logging
-   Input validation and sanitization

## Browser Support

-   Chrome 90+
-   Firefox 88+
-   Safari 14+
-   Edge 90+



## Support

For support and questions:

-   Check the [INSTALLATION.md](INSTALLATION.md) for setup issues
-   Review [TESTING_FLOW.md](TESTING_FLOW.md) for functionality questions
-   Contact the development team for technical support

---

**Built with ❤️ using Laravel, Tailwind CSS, and Alpine.js**

## Quick Start

### Prerequisites

-   PHP 8.0.2 or higher
-   Composer 2.x
-   Node.js 16.x or higher
-   MySQL 8.0+ 

### Installation

1. **Clone the repository**

```bash
git clone https://github.com/agbobli5373/activity-tracking-system.git
cd activity-tracking-system
```

2. **Install dependencies**

```bash
composer install
npm install
```

3. **Environment setup**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**

```bash
# Configure database in .env file
php artisan migrate
php artisan db:seed
```

5. **Build assets and start server**

```bash
npm run build
php artisan serve
```

### Default Login Credentials

-   **Email**: admin@activitytracker.com
-   **Password**: password123

## Application Testing

The application has been thoroughly tested using automated browser testing. See [TESTING_FLOW.md](TESTING_FLOW.md) for comprehensive testing documentation including:

-   Complete user flow testing
-   Feature functionality verification
-   UI/UX validation
-   Screenshots of all major pages
-   Performance and accessibility testing

### Test Results Summary

-   ✅ **Authentication Flow**: Login/logout functionality
-   ✅ **Dashboard**: Activity overview and statistics
-   ✅ **Activity Management**: Create, view, edit, and manage activities
-   ✅ **Reporting**: Generate and export reports
-   ✅ **User Interface**: Modern, responsive design
-   ✅ **Navigation**: Intuitive menu and page transitions

## Features

### Core Functionality

-   **Activity Tracking**: Create, manage, and track daily support activities
-   **Status Management**: Update activity status with detailed history
-   **Team Collaboration**: Assign activities and track progress
-   **Advanced Filtering**: Search and filter activities by multiple criteria
-   **Real-time Updates**: Live activity counters and status updates

### Reporting & Analytics

-   **Custom Reports**: Generate reports with flexible date ranges and filters
-   **Multiple Export Formats**: CSV, PDF, and Excel export options
-   **Activity History**: Complete audit trail for all activities
-   **Performance Metrics**: Team productivity and completion rates

### User Experience

-   **Modern Design**: Professional UI following contemporary design principles
-   **Responsive Layout**: Optimized for desktop, tablet, and mobile devices
-   **Accessibility**: WCAG 2.1 compliant with proper ARIA labels
-   **Fast Performance**: Optimized loading times and smooth interactions

## Development Commands

```bash
# Start development server
php artisan serve

# Watch for asset changes
npm run dev

# Run database migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Clear application cache
php artisan cache:clear

# Run tests
php artisan test
```
