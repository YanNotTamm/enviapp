---
inclusion: always
---

# Technology Stack

## Backend

- **Framework**: CodeIgniter 4 (PHP 8.1+)
- **Database**: MySQL/MariaDB
- **Authentication**: JWT (firebase/php-jwt)
- **Package Manager**: Composer
- **Testing**: PHPUnit

### Backend Requirements

- PHP 8.1 or higher
- Extensions: intl, mbstring, json, mysqlnd, libcurl
- Composer for dependency management

### Backend Commands

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Run tests
composer test
# or
phpunit

# Run development server (from backend/public)
php -S localhost:8080 -t public
```

## Frontend

- **Framework**: React 19.2 with TypeScript
- **Build Tool**: Create React App (react-scripts)
- **Styling**: Tailwind CSS 3.4
- **Routing**: React Router DOM 7.9
- **Forms**: React Hook Form with Yup validation
- **HTTP Client**: Axios
- **Icons**: Lucide React
- **Package Manager**: npm

### Frontend Commands

```bash
# Install dependencies
npm install

# Development server (http://localhost:3000)
npm start

# Production build
npm run build

# Run tests
npm test

# Run tests without watch mode
npm test -- --watchAll=false
```

## Environment Configuration

### Backend (.env)
- Located in `backend/.env`
- Configure: database, JWT secret, encryption key, CORS, email (SMTP)
- Production uses `CI_ENVIRONMENT = production`

### Frontend (.env)
- Located in `frontend/.env`
- Configure: `REACT_APP_API_URL` for backend API endpoint
- Production uses `.env.production`

## Development Workflow

1. Backend runs on port 8080 (or via Apache)
2. Frontend dev server runs on port 3000 with proxy to backend
3. Production build outputs to `frontend/build/`
4. Deployment files prepared in `deployment-ready/` folder

## Cache Management

After deployments, users may need to clear browser cache. Use:
- Hard refresh: Ctrl+Shift+R or Ctrl+F5
- Clear cache helper: `/clear-cache.html`
- Cache busting via query params (e.g., `?v=timestamp`)
