import React from 'react';
import ChatAi from './ChatAi';
import majorsData from '../data/majors.json';
import './Dashboard.css';

const schoolList = [
    {
        code: 'KHA',
        name: 'Đại học Kinh tế Quốc dân',
        city: 'Hà Nội',
        quota: '8.780 chỉ tiêu',
        methods: '7 phương thức tuyển sinh',
        image: 'https://upload.wikimedia.org/wikipedia/commons/5/52/National_Economics_University.jpg',
    },
    {
        code: 'TTN',
        name: 'Đại học Tây Nguyên',
        city: 'Đắk Lắk',
        quota: '4.150 chỉ tiêu',
        methods: '5 phương thức tuyển sinh',
        image: 'https://tuyensinh.ttn.edu.vn/wp-content/uploads/2026/04/nk.jpg',
    },
    {
        code: 'BKA',
        name: 'Đại học Bách khoa Hà Nội',
        city: 'Hà Nội',
        quota: '9.260 chỉ tiêu',
        methods: '4 phương thức tuyển sinh',
        image: 'https://upload.wikimedia.org/wikipedia/commons/6/60/HUST_Hanoi.jpg',
    },
];

function Body() {
    return (
        <main className="dashboard-page bg-light py-4">
            <div className="container">
                <div className="row g-4">
                    <div className="col-lg-8">
                        <h1 className="dashboard-title mb-3">ĐỀ ÁN TUYỂN SINH CÁC TRƯỜNG ĐẠI HỌC 2026</h1>
                        <p className="text-muted fs-5 mb-4">
                            Các trường Đại học đã bắt đầu công bố thông tin tuyển sinh đại học năm 2026.
                            Tra cứu nhanh chỉ tiêu, tổ hợp xét tuyển, ngành và phương thức tuyển sinh phù hợp.
                        </p>

                        <div className="search-banner p-4 mb-4 rounded-3">
                            <input
                                type="text"
                                className="form-control form-control-lg border-0 shadow-sm"
                                placeholder="Nhập tên trường/Mã trường"
                            />
                        </div>

                        <div className="card border-0 shadow-sm mb-4">
                            <div className="card-header bg-white fw-semibold py-3">
                                <i className="bi bi-funnel me-2"></i>
                                Lọc theo
                            </div>
                            <div className="card-body">
                                <div className="row g-3">
                                    <div className="col-md-4">
                                        <label className="form-label">Tỉnh/ thành phố</label>
                                        <select className="form-select">
                                            <option>Tỉnh/Thành phố</option>
                                            <option>Hà Nội</option>
                                            <option>Đắk Lắk</option>
                                            <option>TP.HCM</option>
                                        </select>
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Tổ hợp môn</label>
                                        <input className="form-control" placeholder="Nhập tên tổ hợp/mã tổ hợp" />
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Phương thức xét tuyển</label>
                                        <select className="form-select">
                                            <option>Chọn phương thức xét tuyển</option>
                                            <option>Xét điểm thi THPT</option>
                                            <option>Xét học bạ</option>
                                            <option>Xét tuyển thẳng</option>
                                        </select>
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Chuyên ngành</label>
                                        <select className="form-select">
                                            <option>Chọn ngành/nhóm ngành</option>
                                            {majorsData.slice(0, 20).map((major) => (
                                                <option key={major.code || major.name} value={major.code || major.name}>
                                                    {major.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Loại trường</label>
                                        <select className="form-select">
                                            <option>Loại trường</option>
                                            <option>Công lập</option>
                                            <option>Tư thục</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="vstack gap-3">
                            {schoolList.map((school) => (
                                <div className="card border-0 shadow-sm school-card" key={school.code}>
                                    <div className="card-body p-3 p-md-4">
                                        <div className="d-flex align-items-start gap-3">
                                            <img src={school.image} alt={school.name} className="school-thumb rounded-3" />
                                            <div>
                                                <h5 className="mb-2 fw-bold text-primary">
                                                    {school.code} - {school.name}
                                                </h5>
                                                <p className="mb-1">Địa chỉ: {school.city}</p>
                                                <p className="mb-0 text-muted">
                                                    {school.quota} - {school.methods}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="col-lg-4">
                        <div className="card border-0 shadow-sm sticky-lg-top utility-card" style={{ top: '90px' }}>
                            <div className="card-header utility-header py-4">
                                <h3 className="h4 mb-0 fw-bold">Tính năng hữu ích</h3>
                            </div>
                            <div className="card-body py-4">
                                <ul className="list-unstyled mb-0 d-grid gap-3 fs-5">
                                    <li><a href="#!" className="text-decoration-none"><i className="bi bi-file-earmark-text me-2"></i>Tra cứu đề án tuyển sinh</a></li>
                                    <li><a href="#!" className="text-decoration-none"><i className="bi bi-graph-up-arrow me-2"></i>Tra cứu điểm chuẩn các trường</a></li>
                                    <li><a href="#!" className="text-decoration-none"><i className="bi bi-grid-3x3-gap me-2"></i>Tra cứu tổ hợp môn</a></li>
                                    <li><a href="#!" className="text-decoration-none"><i className="bi bi-search me-2"></i>Tra cứu xếp hạng thi</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ChatAi />
        </main>
    );
}

export default Body;