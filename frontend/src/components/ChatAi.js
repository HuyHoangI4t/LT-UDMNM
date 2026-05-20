import React, { useMemo, useState, useEffect, useRef } from 'react';
import axios from 'axios';
import './ChatAi.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';
const STORAGE_KEY = 'ttn-chat-sessions';
const SELECTED_SESSION_KEY = 'ttn-chat-selected-session';
const CHAT_TIMEOUT_MS = 70000;
const FAQ_TIMEOUT_MS = 15000;

const defaultMessage = 'Mình có thể giúp gì cho bạn?';

const starterQuestions = [
  'Tra cứu ngành Công nghệ thông tin',
  'Tư vấn chọn ngành theo sở thích của em',
  'Điểm chuẩn ngành Kế toán các năm gần đây',
  'Học phí và học bổng của trường',
];

const capabilityGroups = [
  {
    title: 'Tra cứu tuyển sinh',
    icon: 'bi-search',
    items: ['Ngành đào tạo', 'Mã ngành', 'Tổ hợp xét tuyển', 'Chỉ tiêu', 'Điểm chuẩn', 'Học phí'],
  },
  {
    title: 'Tư vấn chọn ngành',
    icon: 'bi-compass',
    items: ['Năng lực', 'Sở thích', 'Định hướng nghề nghiệp', 'Cơ hội việc làm'],
  },
  {
    title: 'AI Chatbot',
    icon: 'bi-cpu',
    items: ['Hiểu ý định', 'Trích xuất thông tin', 'RAG hạn chế sai lệch', 'Hỏi đáp tự nhiên'],
  },
  {
    title: 'Phân tích tuyển sinh',
    icon: 'bi-bar-chart-line',
    items: ['Top ngành được hỏi', 'Thống kê theo thời gian', 'Kênh truy cập', 'Xuất báo cáo'],
  },
];

const createSession = () => ({
  id: `session-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
  title: 'Cuộc trò chuyện mới',
  messages: [{ role: 'ai', text: defaultMessage }],
});

const loadStoredSessions = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : null;

    if (Array.isArray(parsed) && parsed.length > 0) {
      return parsed;
    }
  } catch (error) {
    console.warn('Không đọc được lịch sử chat.', error);
  }

  return [createSession()];
};

const formatSessionTitle = (text) => {
  const trimmed = text.trim().replace(/\s+/g, ' ');
  return trimmed.length <= 45 ? trimmed : `${trimmed.slice(0, 45).trim()}...`;
};

const escapeHtml = (text) =>
  text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const formatMessage = (text) => {
  if (!text) return '';

  let formatted = escapeHtml(text.trim());

  formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  formatted = formatted.replace(
    /(https?:\/\/[^\s<]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
  );
  formatted = formatted.replace(/^\s*[•-]\s+(.+)$/gm, '<li>$1</li>');
  formatted = formatted.replace(/(<li>.*?<\/li>)(\s*<br\/?>\s*<li>.*?<\/li>)*/gs, (match) => {
    const clean = match.replace(/<br\/?>/g, '');
    return `<ul>${clean}</ul>`;
  });
  formatted = formatted
    .replace(/\n{3,}/g, '\n\n')
    .replace(/\n\n/g, '<div class="msg-gap"></div>')
    .replace(/\n/g, '<br/>');

  return formatted;
};

const getGreeting = () => {
  const hour = new Date().getHours();

  if (hour >= 5 && hour < 12) {
    return { text: 'buổi sáng', icon: 'bi-cloud-sun' };
  }

  if (hour >= 12 && hour < 18) {
    return { text: 'buổi chiều', icon: 'bi-sun' };
  }

  return { text: 'buổi tối', icon: 'bi-moon-stars' };
};

const ChatAi = () => {
  const [sessions, setSessions] = useState(loadStoredSessions);
  const [selectedSessionId, setSelectedSessionId] = useState(
    () => localStorage.getItem(SELECTED_SESSION_KEY) || sessions[0].id
  );
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const [suggestedQuestions, setSuggestedQuestions] = useState([]);
  const [suggestedPage, setSuggestedPage] = useState(0);
  const greeting = getGreeting();
  const messagesContainerRef = useRef(null);
  const suggestedPageSize = 3;

  const currentSession = useMemo(
    () =>
      sessions.find((session) => session.id === selectedSessionId) ||
      sessions[0],
    [sessions, selectedSessionId]
  );

  const lastMessage =
    currentSession.messages[currentSession.messages.length - 1];

  const suggestedPageCount = Math.max(
    1,
    Math.ceil(suggestedQuestions.length / suggestedPageSize)
  );

  const visibleSuggestedQuestions = suggestedQuestions.slice(
    suggestedPage * suggestedPageSize,
    suggestedPage * suggestedPageSize + suggestedPageSize
  );

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(sessions.slice(-10)));
  }, [sessions]);

  useEffect(() => {
    localStorage.setItem(SELECTED_SESSION_KEY, selectedSessionId);
  }, [selectedSessionId]);

  useEffect(() => {
    if (lastMessage?.role === 'ai' && messagesContainerRef.current) {
      const container = messagesContainerRef.current;

      requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
      });
    }
  }, [currentSession.messages.length, lastMessage?.role]);

  const canSend = useMemo(
    () => message.trim().length > 0 && !loading,
    [message, loading]
  );

  const updateSession = (sessionId, nextSession) => {
    setSessions((prev) =>
      prev.map((session) => (session.id === sessionId ? nextSession : session))
    );
  };

  const startNewSession = () => {
    if (loading) return;

    const nextSession = createSession();
    setSessions((prev) => [...prev.slice(-9), nextSession]);
    setSelectedSessionId(nextSession.id);
    setMessage('');
    setSuggestedQuestions([]);
    setSuggestedPage(0);
  };

  const renderSuggestedSlots = () => {
    const slots = [];
    for (let i = 0; i < suggestedPageSize; i++) {
      slots.push(visibleSuggestedQuestions[i] ?? null);
    }
    return slots;
  };

  const toggleSpeechRecognition = () => {
    const SpeechRecognition =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      alert('Trình duyệt không hỗ trợ nhập bằng giọng nói.');
      return;
    }

    const recognition = new SpeechRecognition();

    recognition.lang = 'vi-VN';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    setIsListening(true);
    recognition.start();

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      setMessage(transcript);
    };

    recognition.onerror = () => {
      setIsListening(false);
    };

    recognition.onend = () => {
      setIsListening(false);
    };
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
      const response = await axios.post(
        `${API_URL}/chat`,
        {
          message: text,
          platform: 'web',
          history: currentSession.messages.slice(-6),
        },
        {
          headers: {
            'X-Session-ID': sessionId,
          },
          timeout: CHAT_TIMEOUT_MS,
        }
      );

      if (response?.data?.status === 'error') {
        throw new Error(response?.data?.message || 'Chatbot trả về lỗi.');
      }

      const reply =
        response?.data?.data?.reply || 'Mình chưa nhận được phản hồi phù hợp.';

      const aiMessage = { role: 'ai', text: reply };

      updateSession(sessionId, {
        ...currentSession,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      });

      try {
        const q = encodeURIComponent(text);
        const resp = await axios.get(`${API_URL}/faq-questions?q=${q}`, {
          timeout: FAQ_TIMEOUT_MS,
        });
        const qs = resp?.data?.data || [];

        if (qs.length) {
          setSuggestedQuestions(
            qs.map((item) => (typeof item === 'string' ? item : item.question || ''))
          );
          setSuggestedPage(0);
        } else {
          setSuggestedQuestions([]);
        }
      } catch (err) {
        console.warn('Không lấy được câu hỏi gợi ý sau chat.', err);
        setSuggestedQuestions([]);
      }
    } catch (error) {
      console.error('Chat error:', error);

      const aiMessage = {
        role: 'ai',
        text: 'Không thể kết nối chatbot lúc này. Vui lòng thử lại.',
      };

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
      <section className="chat-shell" aria-label="TNU ChatBot">
        <div className="chat-toolbar">
          <div>
            <p className="eyebrow">Tư vấn tuyển sinh</p>
            <h1>{currentSession.title}</h1>
          </div>

          <button
            className="new-chat-btn"
            type="button"
            onClick={startNewSession}
            disabled={loading}
          >
            <i className="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Cuộc mới</span>
          </button>
        </div>

        <div
          className={`chat-messages ${
            currentSession.messages.length <= 1 ? 'welcome-center' : ''
          }`}
          ref={messagesContainerRef}
        >
          {currentSession.messages.length <= 1 && (
            <div className="welcome-hero">
              <span className="welcome-icon">
                <i className={`bi ${greeting.icon}`} aria-hidden="true"></i>
              </span>

              <h2 className="welcome-title">
                Chào {greeting.text}
              </h2>
              <p className="welcome-subtitle">
                Hãy đặt câu hỏi về ngành học, điểm chuẩn, học phí hoặc hồ sơ xét tuyển.
              </p>

              <div className="starter-actions" aria-label="Câu hỏi nhanh">
                {starterQuestions.map((question) => (
                  <button
                    key={question}
                    className="starter-question"
                    type="button"
                    onClick={() => handleSend(question)}
                    disabled={loading}
                  >
                    <i className="bi bi-send" aria-hidden="true"></i>
                    <span>{question}</span>
                  </button>
                ))}
              </div>

              <div className="capability-grid" aria-label="Nhóm chức năng hỗ trợ">
                {capabilityGroups.map((group) => (
                  <article className="capability-card" key={group.title}>
                    <div className="capability-card-header">
                      <span className="capability-icon">
                        <i className={`bi ${group.icon}`} aria-hidden="true"></i>
                      </span>
                      <h3>{group.title}</h3>
                    </div>

                    <div className="capability-tags">
                      {group.items.map((item) => (
                        <span key={item}>{item}</span>
                      ))}
                    </div>
                  </article>
                ))}
              </div>
            </div>
          )}

          {currentSession.messages
            .filter((item, index) => {
              return !(currentSession.messages.length <= 1 && index === 0);
            })
            .map((item, index) => (
              <div
                key={`${item.role}-${index}`}
                className={`message-row ${item.role === 'user' ? 'user' : 'ai'}`}
              >
                <div className="message-avatar" aria-hidden="true">
                  {item.role === 'user' ? (
                    <i className="bi bi-person-fill"></i>
                  ) : (
                    <i className="bi bi-stars"></i>
                  )}
                </div>
                <div
                  className="message-bubble"
                  dangerouslySetInnerHTML={{
                    __html: formatMessage(item.text),
                  }}
                />
              </div>
            ))}

          {loading && (
            <div className="message-row ai">
              <div className="message-avatar" aria-hidden="true">
                <i className="bi bi-stars"></i>
              </div>
              <div className="message-bubble loading-bubble" aria-label="Đang trả lời">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          )}
        </div>

        {suggestedQuestions.length > 0 && (
          <div className="related-box related-bar" aria-label="Câu hỏi gợi ý">
            <button
              className="related-nav-btn"
              type="button"
              onClick={() => setSuggestedPage((p) => Math.max(0, p - 1))}
              disabled={suggestedPage === 0}
              aria-label="Câu hỏi trước"
            >
              <i className="bi bi-chevron-left" aria-hidden="true"></i>
            </button>

            <div className="related-list">
              {renderSuggestedSlots().map((question, idx) => (
                question ? (
                  <button
                    key={`q-${suggestedPage}-${idx}`}
                    className="related-question"
                    type="button"
                    disabled={loading}
                    onClick={() => handleSend(question)}
                  >
                    <i className="bi bi-chat-left-text" aria-hidden="true"></i>
                    <span>{question}</span>
                  </button>
                ) : (
                  <span
                    key={`ph-${suggestedPage}-${idx}`}
                    className="related-question placeholder"
                    aria-hidden="true"
                  />
                )
              ))}
            </div>

            <button
              className="related-nav-btn"
              type="button"
              onClick={() => setSuggestedPage((p) => Math.min(suggestedPageCount - 1, p + 1))}
              disabled={suggestedPage >= suggestedPageCount - 1}
              aria-label="Câu hỏi tiếp theo"
            >
              <i className="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        )}

        <form
          className="chat-input-area"
          onSubmit={(event) => {
            event.preventDefault();
            handleSend();
          }}
        >
          <button
            className={`input-tool-btn mic-btn ${isListening ? 'active' : ''}`}
            type="button"
            onClick={toggleSpeechRecognition}
            aria-label="Nhập bằng giọng nói"
            title="Nhập bằng giọng nói"
          >
            <i className={`bi ${isListening ? 'bi-mic-fill' : 'bi-mic'}`} aria-hidden="true"></i>
          </button>

          <input
            type="text"
            className="chat-input"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            placeholder="Bạn muốn hỏi gì?"
            disabled={loading}
            aria-label="Nội dung câu hỏi"
          />

          <button
            className="send-btn"
            type="submit"
            disabled={!canSend}
            aria-label="Gửi câu hỏi"
            title="Gửi câu hỏi"
          >
            <i className="bi bi-send-fill" aria-hidden="true"></i>
          </button>
        </form>

        <p className="chat-note">
          Thông tin chính thức nên đối chiếu trên website tuyển sinh của trường.
        </p>
      </section>
    </main>
  );
};

export default ChatAi;
