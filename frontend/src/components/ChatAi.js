import React, { useMemo, useState, useEffect, useRef } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const quickQuestions = [
  'Học phí ngành CNTT bao nhiêu?',
  'Trường có xét học bạ không?',
  'Mình muốn học marketing?',
];

const defaultMessage = 'Xin chào! Tôi là trợ lý tuyển sinh. Hãy hỏi tôi về ngành học, học phí, hồ sơ hoặc cơ hội việc làm nhé.';

const createSession = () => ({
  id: `session-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
  title: 'Cuộc trò chuyện mới',
  messages: [{ role: 'ai', text: defaultMessage }],
});

const formatSessionTitle = (text) => {
  const trimmed = text.trim().replace(/\s+/g, ' ');
  return trimmed.length <= 45 ? trimmed : `${trimmed.slice(0, 45).trim()}...`;
};

const ChatAi = () => {
  const [sessions, setSessions] = useState([createSession()]);
  const [selectedSessionId, setSelectedSessionId] = useState(sessions[0].id);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const messagesEndRef = useRef(null);
  const messagesContainerRef = useRef(null);

  const currentSession = useMemo(
    () => sessions.find((session) => session.id === selectedSessionId) || sessions[0],
    [sessions, selectedSessionId]
  );

  const lastMessage = currentSession.messages[currentSession.messages.length - 1];

  useEffect(() => {
    if (lastMessage?.role === 'ai' && messagesContainerRef.current) {
      const container = messagesContainerRef.current;
      requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
      });
    }
  }, [currentSession.messages.length, lastMessage?.role]);

  const canSend = useMemo(() => message.trim().length > 0 && !loading, [message, loading]);

  const addSession = () => {
    const newSession = createSession();
    setSessions((prev) => [newSession, ...prev]);
    setSelectedSessionId(newSession.id);
    setMessage('');
  };

  const updateSession = (sessionId, nextSession) => {
    setSessions((prev) => prev.map((session) => (session.id === sessionId ? nextSession : session)));
  };

  const handleSend = async (customMessage) => {
    const text = (customMessage ?? message).trim();
    if (!text || loading) return;

    const sessionId = currentSession.id;
    const newTitle =
      currentSession.title === 'Cuộc trò chuyện mới'
        ? formatSessionTitle(text)
        : currentSession.title;

    const userMessage = { role: 'user', text };
    const nextMessages = [...currentSession.messages, userMessage];

    updateSession(sessionId, {
      ...currentSession,
      title: newTitle,
      messages: nextMessages,
    });

    setMessage('');
    setLoading(true);

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000);

      const response = await axios.post(
        `${API_URL}/chat`,
        { message: text, platform: 'web' },
        {
          headers: {
            'X-Session-ID': sessionId,
          },
          signal: controller.signal,
        }
      );

      clearTimeout(timeoutId);
      const reply = response?.data?.data?.reply || 'Mình chưa nhận được phản hồi phù hợp.';
      const aiMessage = { role: 'ai', text: reply };
      updateSession(sessionId, {
        ...currentSession,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      });
    } catch (error) {
      console.error('Chat error:', error);
      let errorMsg = 'Không thể kết nối chatbot lúc này. Hãy thử kiểm tra lại server.';
      if (error.code === 'ECONNABORTED') {
        errorMsg = 'Chatbot trả lời chậm. Vui lòng thử lại.';
      }
      const aiMessage = { role: 'ai', text: errorMsg };
      updateSession(sessionId, {
        ...currentSession,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="container-fluid bg-light py-4" style={{ minHeight: '85vh' }}>
      <div className="row gx-0 justify-content-center" style={{ minHeight: '85vh' }}>
        <div className="col-md-3 bg-white border-end" style={{ minHeight: '85vh' }}>
          <div className="p-4 d-flex flex-column h-100">
            <div className="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h5 className="mb-1 fw-bold">Phiên chat</h5>
                <p className="mb-0 text-muted small">Chọn hoặc tạo phiên mới để bắt đầu.</p>
              </div>
              <button className="btn btn-outline-primary btn-sm" onClick={addSession}>
                Cuộc trò chuyện mới
              </button>
            </div>

            <div className="list-group overflow-auto flex-grow-1" style={{ maxHeight: 'calc(85vh - 180px)' }}>
              {sessions.map((session) => (
                <button
                  key={session.id}
                  type="button"
                  className={`list-group-item list-group-item-action rounded-4 mb-2 text-start ${session.id === currentSession.id ? 'active' : ''}`}
                  onClick={() => setSelectedSessionId(session.id)}
                >
                  <div className="fw-semibold text-truncate" style={{ maxWidth: '100%' }}>
                    {session.title}
                  </div>
                  <small className="text-muted d-block text-truncate" style={{ maxWidth: '100%' }}>
                    {session.messages.length > 1 ? `${session.messages.length - 1} câu hỏi` : 'Chưa có câu hỏi'}
                  </small>
                </button>
              ))}
            </div>

            <div className="mt-3 text-muted small">
              <div className="fw-semibold mb-1">Header X-Session-ID</div>
              <div className="text-break">{currentSession.id}</div>
            </div>
          </div>
        </div>

        <div className="col-md-9 d-flex flex-column" style={{ minHeight: '85vh' }}>
          <div className="card border-0 shadow-sm rounded-4 flex-grow-1 d-flex flex-column">
            <div className="card-header bg-white border-0 px-4 py-3">
              <div className="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                  <h5 className="mb-1 fw-bold">Hỏi đáp tuyển sinh AI</h5>
                  <p className="mb-0 text-muted small">Phiên: {currentSession.title}</p>
                </div>
                <div className="text-end">
                  <span className="badge bg-success">Online</span>
                  <div className="text-muted small mt-1">Session ID được gửi vào Header</div>
                </div>
              </div>
            </div>

            <div className="card-body px-4 py-3 d-flex flex-column" style={{ minHeight: 0 }}>
              <div ref={messagesContainerRef} className="overflow-auto mb-3" style={{ maxHeight: 'calc(85vh - 260px)' }}>
                {currentSession.messages.map((item, index) => (
                  <div
                    key={`${item.role}-${index}`}
                    className={`d-flex mb-3 ${item.role === 'user' ? 'justify-content-end' : 'justify-content-start'}`}
                  >
                    <div
                      className={`p-3 rounded-4 shadow-sm ${item.role === 'user' ? 'bg-primary text-white' : 'bg-white border'}`}
                      style={{ maxWidth: '85%' }}
                    >
                      {item.text}
                    </div>
                  </div>
                ))}

                {loading && (
                  <div className="d-flex justify-content-start mb-3">
                    <div className="bg-white p-3 rounded-4 shadow-sm border">
                      <div className="spinner-grow spinner-grow-sm text-primary me-1" role="status"></div>
                      <div className="spinner-grow spinner-grow-sm text-primary me-1" role="status" style={{ animationDelay: '0.2s' }}></div>
                      <div className="spinner-grow spinner-grow-sm text-primary" role="status" style={{ animationDelay: '0.4s' }}></div>
                    </div>
                  </div>
                )}

                <div ref={messagesEndRef} />
              </div>

              <div className="bg-white p-3 rounded-4 border">
                <div className="mb-3">
                  <div className="d-flex flex-wrap gap-2">
                    {quickQuestions.map((question) => (
                      <button
                        key={question}
                        type="button"
                        className="btn btn-outline-secondary btn-sm rounded-pill"
                        disabled={loading}
                        onClick={() => handleSend(question)}
                      >
                        {question}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="input-group">
                  <input
                    type="text"
                    className="form-control bg-light rounded-start-pill ps-3"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                    placeholder="Nhập câu hỏi..."
                    disabled={loading}
                  />
                  <button
                    className="btn btn-primary rounded-end-pill px-4"
                    type="button"
                    onClick={() => handleSend()}
                    disabled={!canSend}
                  >
                    <i className="bi bi-send-fill me-2"></i>Gửi
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  );
};

export default ChatAi;
