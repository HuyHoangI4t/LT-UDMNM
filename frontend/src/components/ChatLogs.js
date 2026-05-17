import React, { useEffect, useState } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

const ChatLogs = () => {
  const [logs, setLogs] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);

  const fetchLogs = async (pageNumber = 1) => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.get(`${API_URL}/chat-logs?page=${pageNumber}`);
      setLogs(response.data.data);
      setLoading(false);
    } catch (err) {
      setLoading(false);
      setError('Không thể tải lịch sử chat. Vui lòng thử lại.');
      console.error('Fetch chat logs error:', err);
    }
  };

  useEffect(() => {
    fetchLogs(page);
  }, [page]);

  const handlePrevPage = () => {
    if (logs && logs.prev_page_url) {
      setPage((prevPage) => Math.max(prevPage - 1, 1));
    }
  };

  const handleNextPage = () => {
    if (logs && logs.next_page_url) {
      setPage((prevPage) => prevPage + 1);
    }
  };

  return (
    <main className="container-fluid bg-light py-4" style={{ minHeight: '85vh' }}>
      <div className="row justify-content-center">
        <div className="col-12 col-xl-10">
          <div className="card border-0 shadow-sm rounded-4">
            <div className="card-header bg-white border-0 px-4 py-3">
              <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                  <h5 className="mb-1 fw-bold">Lịch sử Chat</h5>
                  <p className="mb-0 text-muted small">Danh sách các bản ghi chat đã được lưu vào cơ sở dữ liệu.</p>
                </div>
                <div className="text-end">
                  <a href="/" className="btn btn-outline-primary btn-sm">Quay lại Chatbot</a>
                </div>
              </div>
            </div>

            <div className="card-body p-4">
              {loading && <div className="text-center text-muted">Đang tải dữ liệu...</div>}
              {error && <div className="alert alert-danger">{error}</div>}

              {!loading && !error && (
                <>
                  <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                      <thead className="table-light">
                        <tr>
                          <th scope="col">ID</th>
                          <th scope="col">Session ID</th>
                          <th scope="col">Nền tảng</th>
                          <th scope="col">Câu hỏi</th>
                          <th scope="col">Phản hồi AI</th>
                          <th scope="col">Ngày tạo</th>
                        </tr>
                      </thead>
                      <tbody>
                        {logs?.data?.length > 0 ? (
                          logs.data.map((log) => (
                            <tr key={log.id}>
                              <td>{log.id}</td>
                              <td className="text-break" style={{ maxWidth: '180px' }}>{log.session_id}</td>
                              <td>{log.platform}</td>
                              <td className="text-break" style={{ maxWidth: '280px' }}>{log.user_query}</td>
                              <td className="text-break" style={{ maxWidth: '320px' }}>{log.bot_response}</td>
                              <td>{formatDate(log.created_at)}</td>
                            </tr>
                          ))
                        ) : (
                          <tr>
                            <td colSpan="6" className="text-center text-muted py-4">
                              Chưa có bản ghi chat nào.
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>

                  <div className="d-flex justify-content-between align-items-center mt-4">
                    <div className="text-muted small">
                      Trang {logs?.current_page} / {logs?.last_page} - Tổng {logs?.total} bản ghi
                    </div>
                    <div className="btn-group">
                      <button
                        type="button"
                        className="btn btn-outline-secondary btn-sm"
                        onClick={handlePrevPage}
                        disabled={!logs?.prev_page_url}
                      >
                        Trang trước
                      </button>
                      <button
                        type="button"
                        className="btn btn-outline-secondary btn-sm"
                        onClick={handleNextPage}
                        disabled={!logs?.next_page_url}
                      >
                        Trang sau
                      </button>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </main>
  );
};

export default ChatLogs;
