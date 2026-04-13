import React from 'react';
import './ProductList.css';

function ProductList({ products }) {
  return (
    <section className="products">
      <h2>Products</h2>
      {products.length === 0 ? (
        <p>No products available</p>
      ) : (
        <div className="products-grid">
          {products.map((product) => (
            <div key={product.id} className="product-card">
              <div className="product-id">#{product.id}</div>
              <h3>{product.name}</h3>
              <div className="product-price">${product.price}</div>
            </div>
          ))}
        </div>
      )}
    </section>
  );
}

export default ProductList;
