import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import './scss/style.scss';
import ChatAi from './components/ChatAi';
import Header from './components/Header';
import Body from './components/Body';
import Footer from './components/Footer';

function App() {
  return (
    <div className="app-shell">
      <nav className="sidebar">
        <div className="logo">
          <img src="/images/logo.png" alt="Logo"/>
        </div>
        
        <ul className="sidebar__menu">
          <li className="active"><a href="/">Trang chủ</a></li>
          <li><a href="#majors">Giới thiệu ngành đào tạo</a></li>
          <li><a href="#">Tuyển sinh Đại học</a></li>
          <li><a href="#">Tuyển sinh Sau Đại học</a></li>
          <li><a href="#">Tuyển sinh VHVL</a></li>
          <li><a href="#">Các lớp ngắn hạn</a></li>
          <li><a href="#">Liên kết đào tạo</a></li>
          <li><a href="#">Cơ hội việc làm</a></li>
          <li><a href="#">Đăng ký tư vấn</a></li>
        </ul>

        <div className="sidebar__social">
          <span className="icon">FB</span>
          <span className="icon">YT</span>
          <span className="icon">TK</span>
        </div>

        <div className="sidebar__contact">
          <p>0965.164.445</p>
          <p>tuyensinh@ttn.edu.vn</p>
        </div>

        <div className="sidebar__search">
          <input type="text" placeholder="Search..." />
          <button>🔍</button>
        </div>
      </nav>

      <div className="main-wrapper">
      <header className="hero">
        <div className="hero__content">
          <span className="eyebrow">Tuyển sinh đại học 2026</span>
          <h1>Chọn đúng ngành, mở đúng tương lai</h1>
          <p className="hero__lead">
            Khám phá chương trình học, học phí, cơ hội việc làm và nhận tư vấn ngay qua chatbot.
          </p>

          <div className="hero__actions">
            <a className="btn btn--primary" href="#majors">Khám phá ngành học</a>
            <a className="btn btn--ghost" href="#chatbot">Hỏi chatbot</a>
          </div>

          <div className="stats-grid">
            {highlights.map((item) => (
              <div className="stat-card" key={item.label}>
                <strong>{item.value}</strong>
                <span>{item.label}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="hero__panel">
          <div className="panel-card panel-card--accent">
            <span className="panel-label">Thông báo tuyển sinh</span>
            <h2>Đăng ký học bổng trước 30/06</h2>
            <p>Ưu tiên xét duyệt hồ sơ sớm, hỗ trợ tư vấn và định hướng ngành miễn phí.</p>
          </div>

          <div className="panel-card">
            <span className="panel-label">Quy trình nhập học</span>
            <ol className="step-list">
              {steps.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>
          </div>
        </div>
      </header>

        <main className="main-content">
        <section className="section" id="majors">
          <div className="section-heading">
            <span className="section-tag">Ngành đào tạo</span>
            <h2>Nhóm ngành nổi bật</h2>
          </div>

          <div className="majors-grid">
            {majorsData.map((major) => (
              <article className="major-card" key={major.code || major.name}>
                <div className="major-card__icon">★</div>
                <h3>{major.name}</h3>
                {major.code && <p className="major-code">Mã: {major.code}</p>}
              </article>
            ))}
          </div>
        </section>

        <section className="section section--split" id="chatbot">
          <div className="info-card">
            <span className="section-tag">Tư vấn 24/7</span>
            <h2>Chat bot hỗ trợ tuyển sinh</h2>
            <p>
              Hỏi về ngành học, học phí, tổ hợp xét tuyển, thời gian nhập học hoặc cách nộp hồ sơ.
            </p>
            <ul className="info-list">
              <li>Trả lời nhanh theo dữ liệu tuyển sinh</li>
              <li>Gợi ý ngành học theo điểm và sở thích</li>
              <li>Kết nối trực tiếp với đội tư vấn</li>
            </ul>
          </div>

          <ChatAi />
        </section>

        <section className="section faq-section">
          <div className="section-heading">
            <span className="section-tag">Câu hỏi thường gặp</span>
            <h2>Những điều thí sinh quan tâm</h2>
          </div>

          <div className="faq-grid">
            <div className="faq-card">
              <h3>Học phí bao nhiêu?</h3>
              <p>Mỗi ngành sẽ có mức học phí khác nhau, chatbot sẽ giúp bạn tra cứu nhanh.</p>
            </div>
            <div className="faq-card">
              <h3>Có xét học bạ không?</h3>
              <p>Tuỳ chương trình đào tạo, bạn có thể hỏi bot để xem phương thức xét tuyển phù hợp.</p>
            </div>
            <div className="faq-card">
              <h3>Làm sao đăng ký?</h3>
              <p>Chuẩn bị hồ sơ, nộp online và theo dõi trạng thái ngay trên hệ thống.</p>
            </div>
          </div>
        </section>
      </main>
      </div>
    </div>
  );
}

export default App;