import React, { useEffect, useState } from 'react';
import axios from 'axios';

const AdmissionPage = () => {
    const [admissions, setAdmissions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Link API từ Laravel
    const API_URL = 'http://127.0.0.1:8000/api/admissions';

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await axios.get(API_URL);
                // Xử lý linh hoạt nếu backend bọc trong object 'data' hoặc trả về mảng trực tiếp
                const result = response.data.data || response.data;
                setAdmissions(result);
                setLoading(false);
            } catch (err) {
                console.error("Lỗi gọi API:", err);
                setError("Không thể tải dữ liệu tuyển sinh. Vui lòng kiểm tra kết nối API!");
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    // Hàm định dạng tiền VNĐ (VD: 18000000 -> 18.000.000 ₫)
    const formatVND = (amount) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
        }).format(amount);
    };

    if (loading) return (
        <div className="d-flex justify-content-center align-items-center" style={{minHeight: '400px'}}>
            <div className="spinner-border text-primary" role="status">
                <span className="visually-hidden">Loading...</span>
            </div>
        </div>
    );

    if (error) return (
        <div className="container mt-5">
            <div className="alert alert-danger text-center shadow-sm">{error}</div>
        </div>
    );

    return (
        <div className="container-xxl py-5 bg-light">
            <div className="container">
                <div className="text-center mb-5">
                    <h6 className="section-title bg-light text-center text-primary px-3 uppercase fw-bold">Thông tin đào tạo</h6>
                    <h1 className="display-5 fw-bold text-dark">Các Ngành Tuyển Sinh 2026</h1>
                </div>

                <div className="row g-4">
                    {admissions.map((item) => (
                        <div className="col-lg-4 col-md-6" key={item.id}>
                            <div className="card h-100 border-0 shadow-sm admission-card">
                                <div className="card-header bg-primary text-white py-3 border-0 d-flex justify-content-between align-items-center">
                                    <span className="fw-bold fs-5">Mã: {item.code}</span>
                                    <i className="bi bi-bookmark-star-fill"></i>
                                </div>
                                <div className="card-body p-4">
                                    <h4 className="card-title fw-bold text-primary mb-4" style={{minHeight: '56px'}}>
                                        {item.name}
                                    </h4>
                                    
                                    <div className="mb-3 d-flex align-items-center">
                                        <div className="bg-light p-2 rounded-circle me-3">
                                            <i className="bi bi-book text-primary"></i>
                                        </div>
                                        <div>
                                            <small className="text-muted d-block">Tổ hợp môn</small>
                                            <span className="fw-bold">{item.group}</span>
                                        </div>
                                    </div>

                                    <div className="mb-3 d-flex align-items-center">
                                        <div className="bg-light p-2 rounded-circle me-3">
                                            <i className="bi bi-people text-primary"></i>
                                        </div>
                                        <div>
                                            <small className="text-muted d-block">Chỉ tiêu</small>
                                            <span className="fw-bold">{item.quota} sinh viên</span>
                                        </div>
                                    </div>

                                    <div className="d-flex align-items-center">
                                        <div className="bg-light p-2 rounded-circle me-3">
                                            <i className="bi bi-cash-stack text-success"></i>
                                        </div>
                                        <div>
                                            <small className="text-muted d-block">Học phí dự kiến</small>
                                            <span className="fw-bold text-danger fs-5">{formatVND(item.tuition)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="card-footer bg-white border-0 p-4 pt-0">
                                    <button className="btn btn-outline-primary w-100 rounded-pill fw-bold py-2">
                                        Đăng ký xét tuyển
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <style>{`
                .admission-card {
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    border-radius: 15px;
                    overflow: hidden;
                }
                .admission-card:hover {
                    transform: translateY(-10px);
                    box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
                }
                .section-title {
                    position: relative;
                    display: inline-block;
                    text-transform: uppercase;
                }
                .section-title::before, .section-title::after {
                    content: "";
                    width: 45px;
                    height: 2px;
                    background: var(--bs-primary);
                    position: absolute;
                    top: 50%;
                }
                .section-title::before { left: -55px; }
                .section-title::after { right: -55px; }
            `}</style>
        </div>
    );
};

export default AdmissionPage;