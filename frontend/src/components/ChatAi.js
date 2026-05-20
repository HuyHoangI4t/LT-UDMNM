import React, { useMemo, useState, useEffect, useRef } from 'react';
import axios from 'axios';
import './ChatAi.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const defaultMessage =
  'Xin chào! Tôi là trợ lý tuyển sinh. Hãy hỏi tôi về ngành học, học phí, hồ sơ hoặc cơ hội việc làm nhé.';

const createSession = () => ({
  id: `session-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
  title: 'Cuộc trò chuyện mới',
  messages: [{ role: 'ai', text: defaultMessage }],
});

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

  formatted = formatted.replace(/^\s*[•\-]\s+(.+)$/gm, '<li>$1</li>');

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

const ChatAi = () => {
  const [sessions, setSessions] = useState([createSession()]);
  const [selectedSessionId, setSelectedSessionId] = useState(sessions[0].id);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const [suggestedQuestions, setSuggestedQuestions] = useState([]);

  const messagesContainerRef = useRef(null);

  useEffect(() => {
    const loadSuggestedQuestions = async () => {
      try {
        const response = await axios.get(`${API_URL}/faq-questions`);
        const questions = response?.data?.data || [];

        if (questions.length) {
          setSuggestedQuestions(
            questions
              .map((item) =>
                typeof item === 'string'
                  ? item
                  : item.title || item.question || ''
              )
              .filter(Boolean)
          );
        }
      } catch (error) {
        console.warn('Không lấy được câu hỏi từ DB.', error);
      }
    };

    loadSuggestedQuestions();
  }, []);

  const currentSession = useMemo(
    () =>
      sessions.find((session) => session.id === selectedSessionId) ||
      sessions[0],
    [sessions, selectedSessionId]
  );

  const lastMessage =
    currentSession.messages[currentSession.messages.length - 1];

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

  const toggleSpeechRecognition = () => {
    const SpeechRecognition =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      alert('Trình duyệt không hỗ trợ voice.');
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
        }
      );

      const reply =
        response?.data?.data?.reply || 'Mình chưa nhận được phản hồi phù hợp.';

      const aiMessage = { role: 'ai', text: reply };

      updateSession(sessionId, {
        ...currentSession,
        title: newTitle,
        messages: [...nextMessages, aiMessage],
      });
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
      <div className="chat-shell">
        <div className="chat-messages" ref={messagesContainerRef}>
          {currentSession.messages.map((item, index) => (
            <div
              key={`${item.role}-${index}`}
              className={`message-row ${item.role === 'user' ? 'user' : 'ai'}`}
            >
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
              <div className="message-bubble loading-bubble">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          )}

          {suggestedQuestions.length > 0 && (
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
        </div>

        <div className="chat-input-area">
          <button className="input-tool-btn" type="button">
            <i className="bi bi-image"></i>
          </button>

          <button
            className={`input-tool-btn mic-btn ${isListening ? 'active' : ''}`}
            type="button"
            onClick={toggleSpeechRecognition}
          >
            <i className={`bi ${isListening ? 'bi-mic-fill' : 'bi-mic'}`}></i>
          </button>

          <input
            type="text"
            className="chat-input"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && handleSend()}
            placeholder="💬 Nhập câu hỏi..."
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
          🧾 Mọi thông tin chính thức vui lòng tham khảo website tuyển sinh của trường.
        </div>
      </div>
    </main>
  );
};

export default ChatAi;