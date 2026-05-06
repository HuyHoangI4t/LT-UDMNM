import './App.css';
import { useEffect, useState } from 'react';
import axios from 'axios';
import ChatAi from './components/ChatAi';
import Header from './components/Header';
import ProductList from './components/ProductList';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function App() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [apiHealth, setApiHealth] = useState(null);

  useEffect(() => {
    fetchApiHealth();
    fetchProducts();
  }, []);

  const fetchApiHealth = async () => {
    try {
      const response = await axios.get(`${API_URL}/health`);
      setApiHealth(response.data);
    } catch (err) {
      setError('Failed to connect to API');
      console.error('Health check error:', err);
    }
  };

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/products`);
      setProducts(response.data.data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch products');
      console.error('Fetch error:', err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="App">
      <ChatAi /> abc
      <Header apiHealth={apiHealth} />
      <main className="container">
        {error && <div className="error-message">{error}</div>}
        {loading ? (
          <div className="loading">Loading products...</div>
        ) : (
          <ProductList products={products} />
        )}
      </main>
    </div>
  );
}

export default App;
