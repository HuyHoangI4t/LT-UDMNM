import React from 'react';
import './Header.css';

function Header({ apiHealth }) {
  return (
    <header className="header">
      <div className="header-container">
        <div className="header-title">
          <h1>React & Laravel App</h1>
          <p>Frontend + Backend API</p>
        </div>
        <div className="header-status">
          {apiHealth ? (
            <div className="status-badge success">
              ✓ API Connected
            </div>
          ) : (
            <div className="status-badge loading">
              ⟳ Connecting...
            </div>
          )}
        </div>
      </div>
    </header>
  );
}

export default Header;
