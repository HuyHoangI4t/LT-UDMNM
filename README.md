# Laravel + React Full Stack Application

Complete full-stack web application with Laravel backend and React frontend.

## Project Structure

```
├── backend/          # Laravel API with Swagger
├── frontend/         # React application
└── README.md        # This file
```

## Quick Start

### Backend Setup (Laravel + Swagger)

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Generate Swagger documentation
php artisan l5-swagger:generate

# Start server
php artisan serve
```

The API will be available at: **http://localhost:8000**
Swagger docs at: **http://localhost:8000/api/documentation**

### Frontend Setup (React)

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm start
```

The frontend will be available at: **http://localhost:3000**

## API Documentation

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | API health check |
| GET | `/api/products` | Get all products |

### Example API Response

```json
{
  "data": [
    { "id": 1, "name": "Product 1", "price": 100 },
    { "id": 2, "name": "Product 2", "price": 200 }
  ]
}
```

## Features

### Backend
- ✓ Laravel 10.x
- ✓ Swagger/OpenAPI documentation
- ✓ RESTful API structure
- ✓ Eloquent ORM ready
- ✓ Authentication ready (Sanctum)

### Frontend
- ✓ React 18.x
- ✓ Axios for API calls
- ✓ React Router setup
- ✓ Modern UI with CSS
- ✓ Responsive design
- ✓ API health monitoring

## Requirements

### Backend
- PHP 8.0+
- Composer
- MySQL/MariaDB (optional)

### Frontend
- Node.js 14+
- npm or yarn

## Configuration

### Frontend API URL

Update the API URL in [frontend/src/App.js](frontend/src/App.js):

```javascript
const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';
```

Or create `.env` file:
```
REACT_APP_API_URL=http://localhost:8000/api
```

### Backend CORS

Update Laravel CORS configuration in [backend/config/cors.php](backend/config/cors.php) if needed.

## Development Workflow

1. Start Laravel backend: `php artisan serve` (port 8000)
2. Start React frontend: `npm start` (port 3000)
3. Access app at http://localhost:3000
4. API docs at http://localhost:8000/api/documentation

## Next Steps

1. **Database**: Configure database in `.env` and run migrations
2. **Models**: Create Eloquent models in `app/Models/`
3. **Controllers**: Create API controllers in `app/Http/Controllers/`
4. **Components**: Add more React components in `frontend/src/components/`
5. **Authentication**: Implement Laravel Sanctum authentication

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Swagger Documentation](https://swagger.io/docs/)
- [React Documentation](https://react.dev)
- [OpenAPI Specification](https://swagger.io/specification/)

## License

MIT
