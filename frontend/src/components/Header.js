import React from 'react';
import './Header.css';

function Header() {
  return (
    <>
     

      {/* Navbar Start */}
      <nav className="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="index.html" className="navbar-brand d-flex align-items-center px-4 px-lg-5">
          <h2 className="m-0 text-primary d-flex align-items-center">
            <img src="/images/logo.png" alt="Tuyển sinh TNU" style={{ width: '42px', height: '42px', objectFit: 'cover', marginRight: '12px', borderRadius: '50%' }} />
            
          </h2>
        </a>
        <button type="button" className="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
          <span className="navbar-toggler-icon"></span>
        </button>
        <div className="collapse navbar-collapse" id="navbarCollapse">
          <div className="navbar-nav ms-auto p-4 p-lg-0">
            <a href="/" className="nav-item nav-link active">Trang chủ</a>
            <a href="#majors" className="nav-item nav-link">Giới thiệu ngành đào tạo</a>
            <div className="nav-item dropdown">
              <a
                href="#!"
                className="nav-link dropdown-toggle"
                id="admissionsDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                Tuyển sinh
              </a>
              <div className="dropdown-menu border-0 rounded-0 rounded-bottom m-0 shadow-sm" aria-labelledby="admissionsDropdown">
                <a href="/tuyen-sinh-dh" className="dropdown-item">Tuyển sinh Đại học</a>
                <a href="/tuyen-sinh-sau-dh" className="dropdown-item">Tuyển sinh Sau Đại học</a>
                <a href="/tuyen-sinh-vhvl" className="dropdown-item">Tuyển sinh VHVL</a>
              </div>
            </div>
            <a href="/lop-ngan-han" className="nav-item nav-link">Các lớp ngắn hạn</a>
            <a href="/lien-ket" className="nav-item nav-link">Liên kết đào tạo</a>
            <a href="/co-hoi-viec-lam" className="nav-item nav-link">Cơ hội việc làm</a>
          </div>
          
        </div>
      </nav>
      {/* Navbar End */}
    </>
  );
}

export default Header;