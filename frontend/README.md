# React Frontend

Modern React application that connects to the Laravel API backend.

## Installation

1. Navigate to the frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Create `.env` file (optional, for API URL):
```bash
REACT_APP_API_URL=http://localhost:8000/api
```

## Running the Development Server

```bash
npm start
```

The app will open at `http://localhost:3000`

## Building for Production

```bash
npm run build
```

## Project Structure

- `src/App.js` - Main application component
- `src/components/` - Reusable React components
  - `Header.js` - Header with API status
  - `ProductList.js` - Product display component
- `src/index.js` - Entry point
- `public/index.html` - HTML template

## Features

- ✓ Connects to Laravel API
- ✓ Displays products from backend
- ✓ API health check
- ✓ Modern UI with gradient background
- ✓ Responsive design
