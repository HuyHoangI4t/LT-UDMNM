import React, { useMemo, useState, useEffect, useRef } from 'react';
import axios from 'axios';
import './ChatAi.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

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
  const [suggestedQuestions, setSuggestedQuestions] = useState([]);
  const messagesEndRef = useRef(null);
  const messagesContainerRef = useRef(null);

  useEffect(() => {
    const loadSuggestedQuestions = async () => {
      try {
        const response = await axios.get(`${API_URL}/faq-questions`);
        const questions = response?.data?.data || [];
        if (questions.length) {
          setSuggestedQuestions(
            questions
              .map((item) => (typeof item === 'string' ? item : item.title || item.question || ''))
              .filter(Boolean)
          );
        }
      } catch (error) {
        console.warn('Không lấy được câu hỏi từ DB, dùng mặc định.', error);
      }
    };

    loadSuggestedQuestions();
  }, []);

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
  <main className="chat-page">
    <div className="chat-shell">

      <div className="chat-messages" ref={messagesContainerRef}>
        {currentSession.messages.map((item, index) => (
          <div
            key={`${item.role}-${index}`}
            className={`message-row ${item.role === 'user' ? 'user' : 'ai'}`}
          >
            <div className="message-bubble">
              {item.text}
            </div>
          </div>
        ))}

        {currentSession.messages.length > 2 && suggestedQuestions.length > 0 && (
          <div className="related-box">
            <div className="related-title">💡 Câu hỏi liên quan</div>

            {suggestedQuestions.slice(0, 3).map((question) => (
              <button
                key={question}
                className="related-question"
                disabled={loading}
                onClick={() => handleSend(question)}
              >
                <span>💬</span>
                <span>{question}</span>
                <i className="bi bi-send"></i>
              </button>
            ))}
          </div>
        )}

        {loading && (
          <div className="message-row ai">
            <div className="message-bubble loading-bubble">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        )}

        <div ref={messagesEndRef} />
      </div>

      <div className="chat-input-area">
        <button className="input-tool-btn">
          <i className="bi bi-image"></i>
        </button>

        <button className="input-tool-btn">
          <i className="bi bi-mic"></i>
        </button>

        <input
          type="text"
          className="chat-input"
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && handleSend()}
          placeholder="💬 Nhập câu hỏi hoặc dán ảnh (Ctrl+V)..."
          disabled={loading}
        />

        <button
          className="send-btn"
          type="button"
          onClick={() => handleSend()}
          disabled={!canSend}
        >
          <i className="bi bi-send-fill"></i>
        </button>
      </div>

      <div className="chat-note">
        🧾 Mọi thông tin pháp lý chính thức xin tham khảo văn bản được công bố trên
        <span> website/trang thông báo của trường.</span>
      </div>
    </div>
  </main>
);
};

export default ChatAi;
