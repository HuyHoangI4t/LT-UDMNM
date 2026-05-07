import './App.css';
import ChatAi from './components/ChatAi';

const majors = [
  {
    title: 'Công nghệ thông tin',
    description: 'Lập trình, AI, dữ liệu, an ninh mạng và phát triển phần mềm hiện đại.',
  },
  {
    title: 'Quản trị kinh doanh',
    description: 'Marketing, vận hành, tài chính và kỹ năng quản lý doanh nghiệp.',
  },
  {
    title: 'Thiết kế đồ họa',
    description: 'Thiết kế UI/UX, nhận diện thương hiệu và sáng tạo nội dung số.',
  },
  {
    title: 'Ngôn ngữ Anh',
    description: 'Giao tiếp quốc tế, biên phiên dịch và cơ hội nghề nghiệp rộng mở.',
  },
];

const highlights = [
  { value: '120+', label: 'Ngành & chuyên ngành' },
  { value: '98%', label: 'Sinh viên hài lòng' },
  { value: '30+', label: 'Câu lạc bộ học thuật' },
  { value: '24/7', label: 'Tư vấn tuyển sinh' },
];

const steps = [
  'Tra cứu ngành học phù hợp theo sở thích và điểm số.',
  'Gửi hồ sơ đăng ký trực tuyến chỉ với vài bước.',
  'Nhận phản hồi, lịch phỏng vấn và nhập học nhanh chóng.',
];

function App() {
  return (
    <div className="app-shell">
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
            {majors.map((major) => (
              <article className="major-card" key={major.title}>
                <div className="major-card__icon">★</div>
                <h3>{major.title}</h3>
                <p>{major.description}</p>
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
  );
}

export default App;
