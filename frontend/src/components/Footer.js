import React from 'react';

function Footer() {
    return (
        <div className="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
            <div className="container py-5">
                <div className="row g-5">
                    {/* Cột 1: Liên kết nhanh */}
                    <div className="col-lg-3 col-md-6">
                        <h4 className="text-white mb-3">Liên Kết Nhanh</h4>
                        <a className="btn btn-link" href="#!">Về Chúng Tôi</a>
                        <a className="btn btn-link" href="#!">Liên Hệ</a>
                        <a className="btn btn-link" href="#!">Chính Sách Bảo Mật</a>
                        <a className="btn btn-link" href="#!">Điều Khoản & Điều Kiện</a>
                        <a className="btn btn-link" href="#!">Hỏi Đáp (FAQs)</a>
                    </div>

                    {/* Cột 2: Thông tin liên hệ */}
                    <div className="col-lg-3 col-md-6">
                        <h4 className="text-white mb-3">Thông Tin Liên Hệ</h4>
                        <p className="mb-2"><i className="fa fa-map-marker-alt me-3"></i>567 Lê Duẩn, TP. Buôn Ma Thuột</p>
                        <p className="mb-2"><i className="fa fa-phone-alt me-3"></i>0965.164.445</p>
                        <p className="mb-2"><i className="fa fa-envelope me-3"></i>tuyensinh@ttn.edu.vn</p>
                        <div className="d-flex pt-2">
                            <a className="btn btn-outline-light btn-social" href="#!"><i className="fab fa-facebook-f"></i></a>
                            <a className="btn btn-outline-light btn-social" href="#!"><i className="fab fa-youtube"></i></a>
                            <a className="btn btn-outline-light btn-social" href="#!"><i className="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>

                    {/* Cột 3: Hình ảnh hoạt động (Gallery) */}
                    <div className="col-lg-3 col-md-6">
                        <h4 className="text-white mb-3">Hình Ảnh Hoạt Động</h4>
                        <div className="row g-2 pt-2">
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2026/04/nk.jpg" alt="Gallery 1" />
                            </div>
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=200" alt="Gallery 2" />
                            </div>
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=200" alt="Gallery 3" />
                            </div>
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://images.unsplash.com/photo-1454165833767-02a6ed8a5874?q=80&w=200" alt="Gallery 4" />
                            </div>
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2026/04/TS-2026-177x142.png" alt="Gallery 5" />
                            </div>
                            <div className="col-4">
                                <img className="img-fluid bg-light p-1" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2023/02/banner-7.jpg" alt="Gallery 6" />
                            </div>
                        </div>
                    </div>

                    {/* Cột 4: Đăng ký nhận tin */}
                    <div className="col-lg-3 col-md-6">
                        <h4 className="text-white mb-3">Bản Tin Tuyển Sinh</h4>
                        <p>Đăng ký email để nhận thông báo mới nhất về lịch thi và điểm chuẩn.</p>
                        <div className="position-relative mx-auto" style={{ maxWidth: '400px' }}>
                            <input className="form-control border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Email của bạn" />
                            <button type="button" className="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">Đăng Ký</button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Phần bản quyền bên dưới */}
            <div className="container">
                <div className="copyright">
                    <div className="row">
                        <div className="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a className="border-bottom" href="#!">Đại Học Tây Nguyên 2026</a>, All Right Reserved.
                            <br />
                            Phát triển bởi <a className="border-bottom" href="#!">Your Name</a>
                        </div>
                        <div className="col-md-6 text-center text-md-end">
                            <div className="footer-menu">
                                <a href="#!">Trang chủ</a>
                                <a href="#!">Cookies</a>
                                <a href="#!">Hỗ trợ</a>
                                <a href="#!">Hướng dẫn</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Footer;