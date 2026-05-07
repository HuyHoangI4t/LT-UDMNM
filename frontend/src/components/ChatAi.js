import React, { useMemo, useState } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const quickQuestions = [
    'Học phí ngành công nghệ thông tin bao nhiêu?',
    'Trường có xét học bạ không?',
    'Tôi muốn học marketing thì nên chọn ngành nào?',
];

const defaultMessage =
    'Xin chào! Tôi là trợ lý tuyển sinh. Hãy hỏi tôi về ngành học, học phí, hồ sơ hoặc cơ hội việc làm.';

const ChatAi = () => {
    const [message, setMessage] = useState('');
    const [chatHistory, setChatHistory] = useState([
        { role: 'ai', text: defaultMessage },
    ]);
    const [loading, setLoading] = useState(false);

    const canSend = useMemo(() => message.trim().length > 0 && !loading, [message, loading]);

    const handleSend = async (customMessage) => {
        const text = (customMessage ?? message).trim();
        if (!text || loading) return;

        const updatedHistory = [...chatHistory, { role: 'user', text }];
        setChatHistory(updatedHistory);
        setMessage('');
        setLoading(true);

        try {
            const response = await axios.post(`${API_URL}/chat`, {
                message: text,
                platform: 'web',
            });

            const reply = response?.data?.data?.reply || 'Mình chưa nhận được phản hồi phù hợp.';
            setChatHistory([...updatedHistory, { role: 'ai', text: reply }]);
        } catch (error) {
            console.error('Chat error:', error);
            setChatHistory([
                ...updatedHistory,
                {
                    role: 'ai',
                    text: 'Không thể kết nối chatbot lúc này. Hãy thử lại sau.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    };

    return (
        <section className="chat-card">
            <div className="chat-card__header">
                <div>
                    <span className="section-tag">Chat bot</span>
                    <h2>Hỏi đáp tuyển sinh</h2>
                </div>
                <span className="chat-status">Online</span>
            </div>

            <div className="chat-card__messages" aria-live="polite">
                {chatHistory.map((item, index) => (
                    <div
                        key={`${item.role}-${index}`}
                        className={`chat-bubble chat-bubble--${item.role}`}
                    >
                        {item.text}
                    </div>
                ))}
                {loading && <div className="chat-bubble chat-bubble--ai">Đang trả lời...</div>}
            </div>

            <div className="chat-quick-actions">
                {quickQuestions.map((question) => (
                    <button
                        key={question}
                        type="button"
                        className="chip"
                        onClick={() => handleSend(question)}
                        disabled={loading}
                    >
                        {question}
                    </button>
                ))}
            </div>

            <div className="chat-card__composer">
                <input
                    type="text"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSend()}
                    placeholder="Nhập câu hỏi của bạn..."
                    disabled={loading}
                />
                <button type="button" onClick={() => handleSend()} disabled={!canSend}>
                    {loading ? 'Đang gửi...' : 'Gửi'}
                </button>
            </div>
        </section>
    );
};

export default ChatAi;