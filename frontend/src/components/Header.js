import React from 'react';
import './Header.css';

function Header() {
  return (
    <header className="chatbot-header">

      {/* LEFT */}
      <div className="header-left">

        {/* NAV */}
        <div className="nav-box">

          {/* BACK */}
          <button
            className="icon-btn"
            onClick={() => window.history.back()}
          >
            <i className="fa fa-arrow-left"></i>
          </button>

          {/* HOME */}
          <a
            href="https://tuyensinh.ttn.edu.vn/"
            className="icon-btn"
          >
            <i className="fa fa-home"></i>
          </a>
        </div>

        {/* LOGO */}
        <img
          src="/images/logo.png"
          alt="logo"
          className="header-logo"
        />

        {/* TITLE */}
        <div>
          <h3 className="header-title">
            TNU ChatBot
          </h3>

          <span className="header-subtitle">
            Hỗ trợ tư vấn 24/7
          </span>
        </div>
      </div>

      {/* RIGHT */}
      <div className="header-right">

        {/* LANGUAGE */}
        <div className="language-box">

          <button className="lang-btn active">
            VI
          </button>

          <button className="lang-btn">
            EN
          </button>
        </div>

        {/* SOURCE */}
        <button className="source-btn">
          <i className="fa fa-book"></i>
          <span>Nguồn</span>
        </button>
      </div>
    </header>
  );
}

export default Header;