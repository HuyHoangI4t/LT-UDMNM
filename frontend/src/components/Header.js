import React from 'react';
import './Header.css';

function Header() {
  return (
    <header className="chatbot-header">
      <div className="header-left">
        <div className="nav-box" aria-label="Điều hướng">
          <button
            className="icon-btn"
            type="button"
            onClick={() => window.history.back()}
            aria-label="Quay lại"
            title="Quay lại"
          >
            <i className="fa fa-arrow-left" aria-hidden="true"></i>
          </button>

          <a
            href="https://tuyensinh.ttn.edu.vn/"
            className="icon-btn"
            aria-label="Trang chủ tuyển sinh"
            title="Trang chủ tuyển sinh"
          >
            <i className="fa fa-home" aria-hidden="true"></i>
          </a>
        </div>

        <img src="/images/logo.png" alt="TNU" className="header-logo" />

        <div className="brand-copy">
          <h3 className="header-title">TNU ChatBot</h3>
          <span className="header-subtitle">Hỗ trợ tư vấn tuyển sinh 24/7</span>
        </div>
      </div>

      <nav className="header-menu" aria-label="Liên kết nhanh">
        <a
          className="header-menu-btn active"
          href="https://tuyensinh.ttn.edu.vn/"
          target="_blank"
          rel="noopener noreferrer"
        >
          Thông tin tuyển sinh
        </a>

        <a
          className="header-menu-btn"
          href="https://tuyensinh.ttn.edu.vn/lien-he"
          target="_blank"
          rel="noopener noreferrer"
        >
          Liên hệ
        </a>
      </nav>
    </header>
  );
}

export default Header;
