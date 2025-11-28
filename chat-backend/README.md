# Chat Application Backend (PHP)

A production-ready PHP backend for the chat application with MySQL database, session-based authentication, and circular queue message management.

## Features

- ✅ **User Authentication** - Registration, login, logout with secure sessions
- ✅ **Message Management** - Send, receive, delete messages
- ✅ **File Uploads** - Support for images, videos, audio, and documents
- ✅ **Circular Queue** - Automatic message limit (5 messages max)
- ✅ **Real-time Updates** - Long polling for live message updates
- ✅ **Security** - Rate limiting, CORS, input validation, SQL injection prevention
- ✅ **RESTful API** - Clean JSON API endpoints

## Requirements

- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- PHP extensions: PDO, PDO_MySQL, JSON, Fileinfo

## Installation

### 1. Clone or Download

```bash
cd /path/to/chat-backend
```

### 2. Configure Environment

Copy `.env.example` to `.env` and update with your settings:

```bash
cp .env.example .env
```

Edit `.env`:

```env
DB_HOST=localhost
DB_NAME=chat_app
DB_USER=your_db_user
DB_PASS=your_db_password
FRONTEND_URL=http://localhost:5500
```

### 3. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE chat_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chat_app;
SOURCE database/schema.sql;
EXIT;
```

### 4. Set Permissions

```bash
chmod 755 uploads/
chmod 644 .env
```

### 5. Start Server

**Option A: PHP Built-in Server (Development)**

```bash
php -S localhost:8000
```

**Option B: Apache/Nginx (Production)**

- Configure virtual host to point to backend directory
- Ensure `.htaccess` is enabled (Apache) or configure Nginx rules

## API Endpoints

### Authentication

| Method | Endpoint                 | Description       | Auth Required |
| ------ | ------------------------ | ----------------- | ------------- |
| POST   | `/api/auth/register.php` | Register new user | No            |
| POST   | `/api/auth/login.php`    | Login user        | No            |
| POST   | `/api/auth/logout.php`   | Logout user       | No            |
| GET    | `/api/auth/me.php`       | Get current user  | Yes           |

### Messages

| Method | Endpoint                             | Description           | Auth Required |
| ------ | ------------------------------------ | --------------------- | ------------- |
| GET    | `/api/messages/get.php`              | Get all messages      | Yes           |
| POST   | `/api/messages/send.php`             | Send text message     | Yes           |
| POST   | `/api/messages/upload.php`           | Upload file/media     | Yes           |
| DELETE | `/api/messages/delete.php?id={id}`   | Delete message        | Yes           |
| GET    | `/api/messages/poll.php?lastId={id}` | Long poll for updates | Yes           |

## API Usage Examples

### Register User

```bash
curl -X POST http://localhost:8000/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john_doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login.php \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "username": "john_doe",
    "password": "password123"
  }'
```

### Send Message

```bash
curl -X POST http://localhost:8000/api/messages/send.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "text": "Hello, world!"
  }'
```

### Upload File

```bash
curl -X POST http://localhost:8000/api/messages/upload.php \
  -b cookies.txt \
  -F "file=@/path/to/image.jpg"
```

### Get Messages

```bash
curl http://localhost:8000/api/messages/get.php \
  -b cookies.txt
```

## Project Structure

```
chat-backend/
├── api/
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── me.php
│   └── messages/
│       ├── get.php
│       ├── send.php
│       ├── upload.php
│       ├── delete.php
│       └── poll.php
├── config/
│   ├── config.php
│   ├── database.php
│   └── session.php
├── middleware/
│   ├── auth.php
│   ├── cors.php
│   └── ratelimit.php
├── utils/
│   ├── Response.php
│   ├── Validator.php
│   ├── FileHandler.php
│   └── CircularQueue.php
├── database/
│   └── schema.sql
├── uploads/
│   └── .gitkeep
├── .env.example
├── .env
├── .gitignore
├── .htaccess
└── README.md
```

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **Session Security**: Secure cookies, HTTP-only, SameSite protection
- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: Input sanitization and output escaping
- **CORS**: Configured for specific frontend origin
- **Rate Limiting**: Prevents API abuse
- **File Upload Validation**: MIME type and size checks

## Configuration

### PHP Settings

Ensure these settings in `php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
session.cookie_httponly = 1
session.use_strict_mode = 1
```

### Circular Queue

Messages are limited to 5 per conversation. When a 6th message is sent, the oldest message is automatically deleted. This is configured in `config/config.php`:

```php
define('MESSAGE_QUEUE_LIMIT', 5);
```

## Troubleshooting

### Database Connection Error

- Check MySQL is running
- Verify credentials in `.env`
- Ensure database exists

### File Upload Fails

- Check `uploads/` directory permissions (755)
- Verify PHP upload settings
- Check file size limits

### CORS Errors

- Update `FRONTEND_URL` in `.env`
- Check browser console for specific errors

### Session Not Persisting

- Ensure cookies are enabled
- Check session configuration in `config/session.php`
- Verify `session.save_path` is writable

## Development

- Enable error reporting in `.env`: `APP_ENV=development`
- Check PHP error logs for debugging
- Use browser dev tools to inspect API responses

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Use HTTPS (required for secure cookies)
3. Configure proper web server (Apache/Nginx)
4. Set restrictive file permissions
5. Enable PHP OPcache for performance
6. Consider using database connection pooling
7. Implement proper backup strategy

## License

MIT License
