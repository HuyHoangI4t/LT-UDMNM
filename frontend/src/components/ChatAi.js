import React, { useMemo, useState, useEffect, useRef } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8000/api';

const quickQuestions = [
    'Học phí ngành CNTT bao nhiêu?',
    'Trường có xét học bạ không?',
    'Mình muốn học marketing?',
];

const defaultMessage = 'Xin chào! Tôi là trợ lý tuyển sinh. Hãy hỏi tôi về ngành học, học phí, hồ sơ hoặc cơ hội việc làm nhé.';

const ChatAi = () => {
    // Trạng thái bật/tắt cửa sổ chat
    const [isOpen, setIsOpen] = useState(false);
    
    // Logic của bạn giữ nguyên
    const [message, setMessage] = useState('');
    const [chatHistory, setChatHistory] = useState([
        { role: 'ai', text: defaultMessage },
    ]);
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef(null);

    // Tự động cuộn xuống tin nhắn mới nhất
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [chatHistory, loading]);

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
                    text: 'Không thể kết nối chatbot lúc này. Hãy thử kiểm tra lại server.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            {/* Nút bong bóng Chat nổi */}
            <button 
                onClick={() => setIsOpen(!isOpen)}
                className="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" 
                style={{
                    position: 'fixed', bottom: '30px', right: '30px', 
                    width: '60px', height: '60px', zIndex: 1050, transition: 'all 0.3s'
                }}
            >
                <i className={`bi ${isOpen ? 'bi-x-lg' : 'bi-robot'} fs-3 text-white`}></i>
            </button>

            {/* Khung cửa sổ Chat */}
            {isOpen && (
                <div className="card border-0 shadow-lg" style={{
                    position: 'fixed', bottom: '100px', right: '30px', 
                    width: '380px', zIndex: 1050, borderRadius: '15px', overflow: 'hidden'
                }}>
                    {/* Header */}
                    <div className="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3 border-0">
                        <div className="d-flex align-items-center">
                            <i className="bi bi-robot fs-4 me-2"></i>
                            <div>
                                <h6 className="mb-0 text-white fw-bold">Hỏi đáp tuyển sinh AI</h6>
                                <small className="text-white-50"><i className="bi bi-circle-fill text-success" style={{fontSize:'8px'}}></i> Online</small>
                            </div>
                        </div>
                        <button onClick={() => setIsOpen(false)} className="btn-close btn-close-white"></button>
                    </div>

                    {/* Messages Body */}
                    <div className="card-body bg-light" style={{ height: '350px', overflowY: 'auto' }}>
                        {chatHistory.map((item, index) => (
                            <div key={`${item.role}-${index}`} className={`d-flex mb-3 ${item.role === 'user' ? 'justify-content-end' : 'justify-content-start'}`}>
                                <div className={`p-3 rounded-3 shadow-sm ${item.role === 'user' ? 'bg-primary text-white' : 'bg-white border'}`} 
                                     style={{ maxWidth: '85%', borderBottomRightRadius: item.role === 'user' ? '0' : '0.5rem', borderBottomLeftRadius: item.role === 'ai' ? '0' : '0.5rem' }}>
                                    {item.text}
                                </div>
                            </div>
                        ))}
                        {loading && (
                            <div className="d-flex justify-content-start mb-3">
                                <div className="bg-white p-3 rounded-3 shadow-sm border" style={{ borderBottomLeftRadius: '0' }}>
                                    <div className="spinner-grow spinner-grow-sm text-primary me-1" role="status"></div>
                                    <div className="spinner-grow spinner-grow-sm text-primary me-1" role="status" style={{animationDelay: '0.2s'}}></div>
                                    <div className="spinner-grow spinner-grow-sm text-primary" role="status" style={{animationDelay: '0.4s'}}></div>
                                </div>
                            </div>
                        )}
                        <div ref={messagesEndRef} /> {/* Dùng để cuộn xuống cuối */}
                    </div>

                    {/* Quick Actions (Câu hỏi gợi ý) */}
                    <div className="bg-white px-2 py-2 border-top d-flex flex-wrap gap-1">
                        {quickQuestions.map((question) => (
                            <span 
                                key={question} 
                                onClick={() => !loading && handleSend(question)}
                                className={`badge bg-light text-dark border p-2 ${loading ? 'opacity-50' : 'cursor-pointer'}`}
                                style={{cursor: loading ? 'not-allowed' : 'pointer'}}
                            >
                                {question}
                            </span>
                        ))}
                    </div>

                    {/* Input Area */}
                    <div className="card-footer bg-white border-0 p-3 pt-2">
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
                                className="btn btn-primary rounded-end-pill px-3" 
                                type="button" 
                                onClick={() => handleSend()} 
                                disabled={!canSend}
                            >
                                <i className="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};

export default ChatAi;