import React from 'react';
import ChatAi from './ChatAi';

function Body() {
    return (
        <main>
            {/* Carousel Start */}
            <div className="container-fluid p-0 mb-5 shadow-sm">
                <div id="header-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                    <div className="carousel-inner">
                        <div className="carousel-item active">
                            <img className="img-fluid w-100" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2026/04/3160-CHI-TIEU.png" alt="Banner 1" />
                        </div>
                        <div className="carousel-item">
                            <img className="img-fluid w-100" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2024/01/banner-4.jpg" alt="Banner 2" />
                        </div>
                        <div className="carousel-item">
                            <img className="img-fluid w-100" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2023/02/banner-6.jpg" alt="Banner 3" />
                        </div>
                        <div className="carousel-item">
                            <img className="img-fluid w-100" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2024/02/B101.jpg" alt="Banner 4" />
                        </div>
                        <div className="carousel-item">
                            <img className="img-fluid w-100" src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2023/02/banner-7.jpg" alt="Banner 5" />
                        </div>
                    </div>
                    <button className="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev" style={{ width: "5%" }}>
                        <span className="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                        <span className="visually-hidden">Previous</span>
                    </button>
                    <button className="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next" style={{ width: "5%" }}>
                        <span className="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                        <span className="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            {/* Carousel End */}

            {/* Phương Thức Xét Tuyển Start */}
            <div className="container-xxl py-5">
                <div className="container">
                    <div className="text-center wow fadeInUp" data-wow-delay="0.1s">
                        <h6 className="section-title bg-white text-center text-primary px-3 fw-bold">Tuyển Sinh 2026</h6>
                        <h1 className="mb-5 display-6">Các Phương Thức Xét Tuyển</h1>
                    </div>
                    <div className="row g-4">
                        {[
                            { icon: "bi-award-fill", title: "Xét tuyển thẳng", desc: "Dành cho thí sinh đạt giải HSG quốc gia, quốc tế hoặc theo quy định của Bộ." },
                            { icon: "bi-card-checklist", title: "Xét Học bạ", desc: "Sử dụng kết quả học tập lớp 12 hoặc 3 học kỳ gần nhất để xét tuyển nhanh chóng." },
                            { icon: "bi-mortarboard-fill", title: "Điểm thi THPT", desc: "Xét tuyển dựa trên tổng điểm 3 môn thi tốt nghiệp THPT theo tổ hợp môn." },
                            { icon: "bi-pencil-square", title: "Kỳ thi ĐGNL", desc: "Sử dụng kết quả kỳ thi Đánh giá năng lực của các ĐHQG tổ chức năm 2026." }
                        ].map((item, index) => (
                            <div className="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay={`0.${index * 2 + 1}s`} key={index}>
                                <div className="card h-100 border-0 shadow-sm service-item text-center pt-3 rounded-4">
                                    <div className="card-body p-4">
                                        <div className="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-4" style={{ width: '80px', height: '80px' }}>
                                            <i className={`bi ${item.icon} text-primary fs-1`}></i>
                                        </div>
                                        <h5 className="mb-3 fw-bold">{item.title}</h5>
                                        <p className="text-muted mb-0">{item.desc}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
            {/* Phương Thức Xét Tuyển End */}

  {/* Giới Thiệu Start */}
            <div className="container-xxl py-5 bg-light mt-4">
                <div className="container py-4">
                    <div className="row g-5 align-items-center">
                        <div className="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                            <div className="position-relative h-100">
                                <img className="img-fluid w-100 shadow" 
                                     src="https://tuyensinh.ttn.edu.vn/wp-content/uploads/2026/04/nk.jpg" 
                                     alt="Campus" style={{ objectFit: 'cover' }} />
                            </div>
                        </div>
                        <div className="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                            <h6 className="section-title bg-light text-start text-primary pe-3">Tại sao chọn chúng tôi?</h6>
                            <h1 className="mb-4">Khởi đầu tương lai tại môi trường giáo dục hàng đầu</h1>
                            <p className="mb-4">Chúng tôi tự hào cung cấp một môi trường học tập hiện đại với cơ sở vật chất đạt chuẩn, giúp sinh viên không chỉ nắm vững lý thuyết mà còn thành thạo kỹ năng thực hành.</p>
                            
                            <div className="row gy-3 gx-4 mb-4">
                                {[
                                    "Cơ sở vật chất hiện đại", "Giảng viên tâm huyết",
                                    "Học bổng đa dạng", "Kết nối doanh nghiệp"
                                ].map((feature, idx) => (
                                    <div className="col-sm-6" key={idx}>
                                        <div className="d-flex align-items-center bg-white p-3 shadow-sm border-start border-4 border-primary">
                                            {/* Gỡ bỏ các thẻ div thu hẹp kích thước, chỉ hiển thị icon trực tiếp */}
                                            <i className="bi bi-check-circle-fill text-primary fs-4 me-3"></i>
                                            <h6 className="mb-0">{feature}</h6>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <a className="btn btn-primary py-3 px-5 mt-2" href="#!">Tìm hiểu thêm</a>
                        </div>
                    </div>
                </div>
            </div>
            {/* Giới Thiệu End */}

            {/* Khối Ngành Đào Tạo Start */}
            <div className="container-xxl py-5 mt-4">
                <div className="container">
                    <div className="text-center wow fadeInUp" data-wow-delay="0.1s">
                        <h6 className="section-title bg-white text-center text-primary px-3 fw-bold">Ngành Học</h6>
                        <h1 className="mb-5 display-6">Các Nhóm Ngành Nổi Bật</h1>
                    </div>
                    <div className="row g-4">
                        {/* Ngành 1 */}
                        <div className="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.1s">
                            <div className="card text-white border-0 overflow-hidden h-100 rounded-4 shadow-sm course-card">
                                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=2070&auto=format&fit=crop" className="card-img h-100" alt="Pedagogy" style={{ objectFit: 'cover' }} />
                                <div className="card-img-overlay d-flex flex-column justify-content-end p-4" style={{ background: 'linear-gradient(to top, rgba(0,0,0,0.8), transparent)' }}>
                                    <h4 className="card-title text-white fw-bold mb-2">Khối ngành Sư Phạm</h4>
                                    <p className="card-text mb-0"><a href="#!" className="text-white text-decoration-none">Xem chi tiết <i className="bi bi-arrow-right ms-1"></i></a></p>
                                </div>
                            </div>
                        </div>
                        {/* Ngành 2 */}
                        <div className="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.3s">
                            <div className="card text-white border-0 overflow-hidden h-100 rounded-4 shadow-sm course-card">
                                <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2070&auto=format&fit=crop" className="card-img h-100" alt="Tech" style={{ objectFit: 'cover' }} />
                                <div className="card-img-overlay d-flex flex-column justify-content-end p-4" style={{ background: 'linear-gradient(to top, rgba(0,0,0,0.8), transparent)' }}>
                                    <h4 className="card-title text-white fw-bold mb-2">Kỹ thuật & Công nghệ</h4>
                                    <p className="card-text mb-0"><a href="#!" className="text-white text-decoration-none">Xem chi tiết <i className="bi bi-arrow-right ms-1"></i></a></p>
                                </div>
                            </div>
                        </div>
                        {/* Ngành 3 */}
                        <div className="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.5s">
                            <div className="card text-white border-0 overflow-hidden h-100 rounded-4 shadow-sm course-card">
                                <img src="https://cdn.prod.website-files.com/6705f4b15aee7ca914fff083/68fa587164e8890b2aad7a47_image.png" className="card-img h-100" alt="Economics" style={{ objectFit: 'cover' }} />
                                <div className="card-img-overlay d-flex flex-column justify-content-end p-4" style={{ background: 'linear-gradient(to top, rgba(0,0,0,0.8), transparent)' }}>
                                    <h4 className="card-title text-white fw-bold mb-2">Kinh tế & Quản trị</h4>
                                    <p className="card-text mb-0"><a href="#!" className="text-white text-decoration-none">Xem chi tiết <i className="bi bi-arrow-right ms-1"></i></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {/* Khối Ngành Đào Tạo End */}

            {/* Form Tư Vấn Split-Card Start */}
            <div className="container-xxl py-5 mt-4 mb-5">
                <div className="container">
                    <div className="card border-0 shadow-lg overflow-hidden rounded-4">
                        <div className="row g-0">
                            {/* Cột trái: Thông tin liên hệ (Nền xanh) */}
                            <div className="col-lg-5 bg-primary text-white p-5 d-flex flex-column justify-content-center">
                                <h2 className="text-white mb-4 fw-bold">Liên hệ tư vấn ngay</h2>
                                <p className="mb-4 opacity-75">Thí sinh và phụ huynh có thắc mắc về hồ sơ, điểm chuẩn hay học phí? Đừng ngần ngại để lại thông tin, đội ngũ tư vấn sẽ liên hệ trong thời gian sớm nhất!</p>
                                
                                <div className="d-flex align-items-center mb-4">
                                    <div className="btn-lg-square bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style={{ width: '60px', height: '60px' }}>
                                        <i className="bi bi-telephone-fill fs-3"></i>
                                    </div>
                                    <div className="ms-4">
                                        <p className="mb-1 opacity-75">Hotline Tuyển sinh</p>
                                        <h4 className="text-white mb-0 fw-bold">0965.164.445</h4>
                                    </div>
                                </div>
                                
                                <div className="d-flex align-items-center">
                                    <div className="btn-lg-square bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style={{ width: '60px', height: '60px' }}>
                                        <i className="bi bi-envelope-fill fs-3"></i>
                                    </div>
                                    <div className="ms-4">
                                        <p className="mb-1 opacity-75">Email Hỗ trợ</p>
                                        <h5 className="text-white mb-0 fw-bold">tuyensinh@ttn.edu.vn</h5>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Cột phải: Form nhập liệu (Nền trắng) */}
                            <div className="col-lg-7 bg-white p-5">
                                <h3 className="mb-4 fw-bold text-dark">Đăng ký nhận thông tin</h3>
                                <form>
                                    <div className="row g-3">
                                        <div className="col-md-6">
                                            <div className="form-floating">
                                                <input type="text" className="form-control bg-light border-0" id="name" placeholder="Họ tên" />
                                                <label htmlFor="name">Họ và tên thí sinh</label>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="form-floating">
                                                <input type="text" className="form-control bg-light border-0" id="phone" placeholder="SĐT" />
                                                <label htmlFor="phone">Số điện thoại / Zalo</label>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="form-floating">
                                                <select className="form-select bg-light border-0" id="select1">
                                                    <option defaultValue="">-- Vui lòng chọn ngành --</option>
                                                    <option value="1">Y Đa khoa</option>
                                                    <option value="2">Công nghệ thông tin</option>
                                                    <option value="3">Sư phạm Toán</option>
                                                    <option value="4">Ngôn ngữ Anh</option>
                                                    <option value="5">Quản trị Kinh doanh</option>
                                                </select>
                                                <label htmlFor="select1">Ngành bạn dự định đăng ký</label>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="form-floating mb-3">
                                                <textarea className="form-control bg-light border-0" placeholder="Ghi chú thêm" id="message" style={{ height: '100px' }}></textarea>
                                                <label htmlFor="message">Câu hỏi hoặc thắc mắc (Nếu có)</label>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <button className="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-sm" type="submit">GỬI YÊU CẦU TƯ VẤN</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {/* Form Tư Vấn End */}

            <ChatAi />
        </main>
    );
}

export default Body;