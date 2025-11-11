---
inclusion: always
---

# Project Structure

## Root Level

```
/
├── backend/              # CodeIgniter 4 API backend
├── frontend/             # React TypeScript frontend
├── deployment-ready/     # Production-ready deployment files
├── .htaccess            # Apache root routing configuration
├── index.php            # Root entry point (redirects to backend)
└── *.sql, *.md, *.txt   # Setup scripts and documentation
```

## Backend Structure (CodeIgniter 4)

```
backend/
├── app/
│   ├── Config/          # Configuration files (database, routes, etc.)
│   ├── Controllers/     # API controllers
│   ├── Models/          # Database models
│   ├── Filters/         # Request/response filters (CORS, Auth)
│   ├── Libraries/       # Custom libraries
│   ├── Validation/      # Custom validation rules
│   ├── Database/        # Migrations and seeds
│   ├── Helpers/         # Helper functions
│   └── Views/           # View templates (if any)
├── public/              # Web root (index.php entry point)
├── writable/            # Logs, cache, uploads (writable by server)
├── vendor/              # Composer dependencies
├── tests/               # PHPUnit tests
├── .env                 # Environment configuration
└── composer.json        # PHP dependencies
```

### Backend Conventions

- Controllers in `app/Controllers/` handle API endpoints
- Models in `app/Models/` extend `CodeIgniter\Model`
- Routes defined in `app/Config/Routes.php`
- API endpoints prefixed with `/api`
- JWT authentication via custom filters
- PSR-4 autoloading: `App\` namespace maps to `app/`

## Frontend Structure (React + TypeScript)

```
frontend/
├── src/
│   ├── components/      # Reusable React components
│   ├── contexts/        # React Context providers (Auth, etc.)
│   ├── utils/           # Utility functions and helpers
│   ├── tests/           # Test files
│   ├── App.tsx          # Main application component
│   └── index.tsx        # Application entry point
├── public/              # Static assets (index.html, icons)
├── build/               # Production build output (generated)
├── node_modules/        # npm dependencies
├── .env                 # Environment variables
├── package.json         # npm dependencies and scripts
├── tsconfig.json        # TypeScript configuration
└── tailwind.config.js   # Tailwind CSS configuration
```

### Frontend Conventions

- TypeScript for type safety
- Functional components with hooks
- React Hook Form for form handling
- Axios for API calls to backend
- Tailwind CSS for styling (utility-first)
- Custom color palette defined in tailwind.config.js
- Context API for global state (authentication)
- React Router for client-side routing

## Deployment Structure

```
deployment-ready/
├── backend/             # Production backend files
├── frontend/            # Production frontend build
└── *.php                # Utility scripts (cache clearing, email testing)
```

## Key Files

- `backend/.env` - Backend environment configuration (database, JWT, CORS)
- `frontend/.env` - Frontend environment configuration (API URL)
- `backend/app/Config/Routes.php` - API route definitions
- `frontend/src/App.tsx` - Main React application with routing
- `.htaccess` files - Apache rewrite rules for routing

## Naming Conventions

- **Backend**: PascalCase for classes, camelCase for methods
- **Frontend**: PascalCase for components, camelCase for functions/variables
- **Files**: PascalCase for component files, lowercase for utilities
- **Database**: snake_case for table and column names
