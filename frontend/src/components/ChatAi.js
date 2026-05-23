import React, { useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import './ChatAi.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';
const STORAGE_KEY = 'ttn-chat-sessions';
const SELECTED_SESSION_KEY = 'ttn-chat-selected-session';
const CHAT_TIMEOUT_MS = 70000;
const FAQ_TIMEOUT_MS = 15000;

const defaultMessage = 'Mình có thể giúp gì cho bạn?';

const fallbackStarterQuestions = [
  'Tra cứu ngành Công nghệ thông tin',
  'Tư vấn chọn ngành theo sở thích của em',
  'Điểm chuẩn ngành Kế toán các năm gần đây',
  'Học phí và học bổng của trường',
];

const normalizeFaqQuestions = (items, limit = 12) => {
  const seen = new Set();

  return (Array.isArray(items) ? items : [])
    .map((item) => (typeof item === 'string' ? item : item?.question || ''))
    .map((item) => item.trim())
    .filter(Boolean)
    .filter((item) => {
      const key = item.toLocaleLowerCase('vi-VN');

      if (seen.has(key)) {
        return false;
      }

      seen.add(key);
      return true;
    })
    .slice(0, limit);
};

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
  messages: [createChatMessage('ai', defaultMessage)],
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

const createChatMessage = (role, text, extra = {}) => ({
  id: `msg-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
  role,
  text,
  createdAt: new Date().toISOString(),
  ...extra,
});

const formatMessageTime = (value) => {
  const date = value ? new Date(value) : new Date();

  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const ChatAi = () => {
  const [sessions, setSessions] = useState(loadStoredSessions);
  const [selectedSessionId, setSelectedSessionId] = useState(
    () => localStorage.getItem(SELECTED_SESSION_KEY) || sessions[0].id
  );
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const [copiedMessageId, setCopiedMessageId] = useState('');
  const [starterQuestions, setStarterQuestions] = useState(fallbackStarterQuestions);
  const [suggestedQuestions, setSuggestedQuestions] = useState([]);
  const [suggestedPage, setSuggestedPage] = useState(0);
  const messagesContainerRef = useRef(null);
  const recognitionRef = useRef(null);
  const activeRequestRef = useRef(null);
  const sendLockRef = useRef(false);
  const mountedRef = useRef(true);
  const suggestedPageSize = 3;
  const greeting = getGreeting();

  const currentSession = useMemo(
    () => sessions.find((session) => session.id === selectedSessionId) || sessions[0],
    [sessions, selectedSessionId]
  );

  const lastMessage = currentSession.messages[currentSession.messages.length - 1];
  const isReplyPending = loading && lastMessage?.role === 'user';
  const canSend = useMemo(() => message.trim().length > 0 && !isReplyPending, [message, isReplyPending]);
  const suggestedPageCount = Math.max(1, Math.ceil(suggestedQuestions.length / suggestedPageSize));
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
    return () => {
      mountedRef.current = false;
      recognitionRef.current?.abort?.();
      activeRequestRef.current?.abort?.();
    };
  }, []);

  useEffect(() => {
    const controller = new AbortController();

    axios.get(`${API_URL}/faq-questions`, {
      params: { limit: 4 },
      timeout: FAQ_TIMEOUT_MS,
      signal: controller.signal,
    })
      .then((resp) => {
        if (!mountedRef.current) return;

        const questions = normalizeFaqQuestions(resp?.data?.data, 4);
        setStarterQuestions(questions.length ? questions : fallbackStarterQuestions);
      })
      .catch((err) => {
        if (axios.isCancel(err) || err?.code === 'ERR_CANCELED') {
          return;
        }

        console.warn('Không lấy được FAQ ban đầu.', err);
        if (mountedRef.current) {
          setStarterQuestions(fallbackStarterQuestions);
        }
      });

    return () => controller.abort();
  }, []);

  useEffect(() => {
    if (lastMessage?.role === 'ai' && messagesContainerRef.current) {
      const container = messagesContainerRef.current;
      requestAnimationFrame(() => {
        container.scrollTop = container.scrollHeight;
      });
    }
  }, [currentSession.messages.length, lastMessage?.role]);

  const updateSession = (sessionId, nextSessionOrUpdater) => {
    setSessions((prev) =>
      prev.map((session) => {
        if (session.id !== sessionId) {
          return session;
        }

        return typeof nextSessionOrUpdater === 'function'
          ? nextSessionOrUpdater(session)
          : nextSessionOrUpdater;
      })
    );
  };

  const copyMessage = async (text, messageId) => {
    try {
      await navigator.clipboard.writeText(text || '');
      setCopiedMessageId(messageId);
      window.setTimeout(() => {
        setCopiedMessageId((current) => (current === messageId ? '' : current));
      }, 1600);
    } catch (error) {
      console.warn('Không sao chép được nội dung chat.', error);
    }
  };

  const setMessageFeedback = (sessionId, messageIndex, feedback) => {
    updateSession(sessionId, (session) => ({
      ...session,
      messages: session.messages.map((item, index) =>
        index === messageIndex
          ? { ...item, feedback: item.feedback === feedback ? undefined : feedback }
          : item
      ),
    }));
  };

  const startNewSession = () => {
    if (isReplyPending) return;

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
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      alert('Trình duyệt không hỗ trợ nhập bằng giọng nói.');
      return;
    }

    recognitionRef.current?.abort?.();

    const recognition = new SpeechRecognition();
    recognitionRef.current = recognition;
    recognition.lang = 'vi-VN';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    setIsListening(true);
    recognition.start();

    recognition.onresult = (event) => {
      setMessage(event.results[0][0].transcript);
    };

    recognition.onerror = () => {
      setIsListening(false);
    };

    recognition.onend = () => {
      setIsListening(false);
      recognitionRef.current = null;
    };
  };

  const handleSend = async (customMessage) => {
    const text = (customMessage ?? message).trim();

    if (!text || isReplyPending || sendLockRef.current) return;
    sendLockRef.current = true;

    const sessionId = currentSession.id;
    const newTitle =
      currentSession.title === 'Cuộc trò chuyện mới'
        ? formatSessionTitle(text)
        : currentSession.title;
    const userMessage = createChatMessage('user', text);
    const nextMessages = [...currentSession.messages, userMessage];

    updateSession(sessionId, {
      ...currentSession,
      title: newTitle,
      messages: nextMessages,
    });

    setMessage('');
    setSuggestedQuestions([]);
    setSuggestedPage(0);
    setLoading(true);
    activeRequestRef.current?.abort?.();
    const controller = new AbortController();
    activeRequestRef.current = controller;

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
          signal: controller.signal,
        }
      );

      if (response?.data?.status === 'error') {
        throw new Error(response?.data?.message || 'Chatbot trả về lỗi.');
      }

      const reply = response?.data?.data?.reply || 'Mình chưa nhận được phản hồi phù hợp.';
      const aiMessage = createChatMessage('ai', reply, { retryText: text });

      updateSession(sessionId, (session) => ({
        ...session,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      }));

      if (mountedRef.current) {
        setLoading(false);
      }

      void axios.get(`${API_URL}/faq-questions`, {
        params: {
          q: text,
          session_id: sessionId,
        },
        timeout: FAQ_TIMEOUT_MS,
      })
        .then((resp) => {
          if (!mountedRef.current) return;

          const qs = normalizeFaqQuestions(resp?.data?.data);

          if (qs.length) {
            setSuggestedQuestions(qs);
            setSuggestedPage(0);
          } else {
            setSuggestedQuestions([]);
          }
        })
        .catch((err) => {
          if (!mountedRef.current) return;

          console.warn('Không lấy được câu hỏi gợi ý sau chat.', err);
          setSuggestedQuestions([]);
        });
    } catch (error) {
      if (axios.isCancel(error) || error?.code === 'ERR_CANCELED') {
        return;
      }

      console.error('Chat error:', error);

      const aiMessage = createChatMessage('ai', 'Không thể kết nối chatbot lúc này. Vui lòng thử lại.', {
        error: true,
        retryText: text,
      });

      updateSession(sessionId, (session) => ({
        ...session,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      }));
    } finally {
      if (activeRequestRef.current === controller) {
        activeRequestRef.current = null;
      }
      sendLockRef.current = false;
      if (mountedRef.current) {
        setLoading(false);
      }
    }
  };

  const renderMessageText = (text) => (
    <ReactMarkdown
      remarkPlugins={[remarkGfm]}
      components={{
        a: ({ href, children }) => {
          const safeHref = typeof href === 'string' && /^https?:\/\//i.test(href)
            ? href
            : undefined;

          return safeHref ? (
            <a href={safeHref} target="_blank" rel="noopener noreferrer">
              {children}
            </a>
          ) : (
            <span>{children}</span>
          );
        },
      }}
    >
      {text || ''}
    </ReactMarkdown>
  );

  return (
    <main className="chat-page">
      <section className="chat-shell" aria-label="TNU ChatBot">
        <div className="chat-toolbar">
          <div>
            <p className="eyebrow">Tư vấn tuyển sinh</p>
            <h1>{currentSession.title}</h1>
          </div>

          <button className="new-chat-btn" type="button" onClick={startNewSession} disabled={isReplyPending}>
            <i className="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Cuộc mới</span>
          </button>
        </div>

        <div
          className={`chat-messages ${currentSession.messages.length <= 1 ? 'welcome-center' : ''}`}
          ref={messagesContainerRef}
        >
          {currentSession.messages.length <= 1 && (
            <div className="welcome-hero">
              <span className="welcome-icon">
                <i className={`bi ${greeting.icon}`} aria-hidden="true"></i>
              </span>

              <h2 className="welcome-title">Chào {greeting.text}</h2>
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
                    disabled={isReplyPending}
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

          {currentSession.messages.map((item, index) => {
            if (currentSession.messages.length <= 1 && index === 0) {
              return null;
            }

            const messageId = item.id || `${item.role}-${index}`;
            const previousUserMessage = [...currentSession.messages]
              .slice(0, index)
              .reverse()
              .find((messageItem) => messageItem.role === 'user');
            const retryText = item.retryText || previousUserMessage?.text || '';

            return (
              <div
                key={messageId}
                className={`message-row ${item.role === 'user' ? 'user' : 'ai'}`}
              >
                <div className="message-avatar" aria-hidden="true">
                  {item.role === 'user' ? (
                    <i className="bi bi-person-fill"></i>
                  ) : (
                    <i className="bi bi-stars"></i>
                  )}
                </div>
                <div className="message-content">
                  <div className={`message-bubble ${item.error ? 'error-bubble' : ''}`}>
                    {renderMessageText(item.text)}
                  </div>

                  <div className="message-meta">
                    <span className="message-time">{formatMessageTime(item.createdAt)}</span>

                    <button
                      className="message-action-btn"
                      type="button"
                      onClick={() => copyMessage(item.text, messageId)}
                      aria-label="Sao chép"
                      title="Sao chép"
                    >
                      <i className={`bi ${copiedMessageId === messageId ? 'bi-check2' : 'bi-copy'}`} aria-hidden="true"></i>
                      <span>{copiedMessageId === messageId ? 'Đã chép' : 'Sao chép'}</span>
                    </button>

                    {item.role === 'ai' && (
                      <>
                        <button
                          className="message-action-btn"
                          type="button"
                          disabled={isReplyPending || !retryText}
                          onClick={() => handleSend(retryText)}
                          aria-label="Gửi lại"
                          title="Gửi lại"
                        >
                          <i className="bi bi-arrow-clockwise" aria-hidden="true"></i>
                          <span>Gửi lại</span>
                        </button>

                        <button
                          className={`message-icon-btn ${item.feedback === 'like' ? 'active' : ''}`}
                          type="button"
                          onClick={() => setMessageFeedback(currentSession.id, index, 'like')}
                          aria-label="Thích"
                          title="Thích"
                        >
                          <i className="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                        </button>

                        <button
                          className={`message-icon-btn ${item.feedback === 'dislike' ? 'active' : ''}`}
                          type="button"
                          onClick={() => setMessageFeedback(currentSession.id, index, 'dislike')}
                          aria-label="Không thích"
                          title="Không thích"
                        >
                          <i className="bi bi-hand-thumbs-down" aria-hidden="true"></i>
                        </button>
                      </>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          {isReplyPending && (
            <div className="message-row ai">
              <div className="message-avatar" aria-hidden="true">
                <i className="bi bi-stars"></i>
              </div>
              <div className="message-bubble thinking-bubble" aria-label="Đang suy nghĩ">
                Đang suy nghĩ ...
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
              {renderSuggestedSlots().map((question, idx) =>
                question ? (
                  <button
                    key={`q-${suggestedPage}-${idx}`}
                    className="related-question"
                    type="button"
                    disabled={isReplyPending}
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
              )}
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
            disabled={isReplyPending}
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
