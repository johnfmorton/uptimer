# Uptime Monitor Documentation

Welcome to the Uptime Monitor documentation. This Laravel-based application helps you monitor website availability and receive notifications when sites go down or recover.

## Documentation Index

### [Setup Guide](setup.md)
Complete installation and configuration instructions including:
- Prerequisites and installation steps
- Environment variable configuration
- Database setup
- Queue worker and scheduler configuration
- Email and Pushover notification setup
- Troubleshooting common setup issues

### [Usage Guide](usage.md)
Learn how to use the application effectively:
- Dashboard overview and navigation
- Adding, editing, and deleting monitors
- Configuring notification settings
- Understanding check results and uptime statistics
- Best practices for monitoring
- Troubleshooting common issues

## Quick Start

1. **Install the application** - Follow the [Setup Guide](setup.md)
2. **Create your first monitor** - See [Managing Monitors](usage.md#managing-monitors)
3. **Configure notifications** - See [Notification Settings](usage.md#notification-settings)
4. **Monitor your sites** - View the [Dashboard Overview](usage.md#dashboard-overview)

## Features

- 🔍 **HTTP Monitoring**: Periodic checks of website availability
- 📧 **Email Notifications**: Receive alerts via email when status changes
- 📱 **Pushover Integration**: Get instant push notifications on your mobile device
- 📊 **Uptime Statistics**: Track reliability with 24h, 7d, and 30d uptime percentages
- 📝 **Check History**: View detailed history of all checks with timestamps and errors
- ⚡ **Background Processing**: Asynchronous checks using Laravel queues
- 🔐 **User Authentication**: Secure access with Laravel authentication
- 🎨 **Modern UI**: Clean interface built with Tailwind CSS

## System Requirements

- PHP 8.2 or higher
- Composer 2
- Node.js and NPM
- MySQL 8.4 or SQLite
- DDEV (for local development)

## Architecture Overview

The application follows Laravel's MVC architecture with additional service layers:

```
Web Interface (Blade + Tailwind CSS)
         ↓
    Controllers
         ↓
   Service Layer (MonitorService, CheckService, NotificationService)
         ↓
    Models & Queue Jobs
         ↓
      Database
```

### Key Components

- **MonitorService**: Handles monitor CRUD operations
- **CheckService**: Executes HTTP checks and records results
- **NotificationService**: Sends email and Pushover notifications
- **Queue Jobs**: Background processing for checks
- **Scheduler**: Triggers periodic checks every minute

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Blade templates, Tailwind CSS 4, Alpine.js
- **Database**: SQLite (default) or MySQL 8.4
- **Queue**: Database driver (can migrate to Redis)
- **Development**: DDEV local environment

## Getting Help

- **Setup Issues**: See [Setup Guide - Troubleshooting](setup.md#troubleshooting)
- **Usage Questions**: See [Usage Guide - Troubleshooting](usage.md#troubleshooting)
- **Application Logs**: Run `ddev artisan pail` to view real-time logs

## Contributing

This application was built following Laravel best practices and includes:
- Comprehensive test coverage (unit, feature, and property-based tests)
- PSR-12 code style (enforced with Laravel Pint)
- Type-safe code with strict typing
- Service layer architecture for maintainability

## License

This application is open-sourced software licensed under the MIT license.

## Support

For additional support or questions:
1. Review the documentation thoroughly
2. Check application logs for errors
3. Contact your system administrator or development team

---

**Next Steps:**
- New users: Start with the [Setup Guide](setup.md)
- Existing users: Jump to the [Usage Guide](usage.md)
