import React, { useState } from 'react';
import axios from 'axios';
import ReactMarkdown from 'react-markdown';

const ChatAi = () => {
    const [message, setMessage] = useState('');
    const [chatHistory, setChatHistory] = useState([]);
    const [loading, setLoading] = useState(false);

    const handleSend = async () => {
        if (!message.trim()) return;

        const newHistory = [...chatHistory, { role: 'user', text: message }];
        setChatHistory(newHistory);
        setMessage('');
        setLoading(true);

        try {
            const response = await axios.post('http://127.0.0.1:8000/api/chat', {
                message: message,
                platform: 'web'
            });

            if (response.data.status === 'success') {
                // FIX Ở ĐÂY: Lấy response.data.data.reply (là chuỗi văn bản)
                // thay vì response.data.data (là object)
                const aiReply = response.data.data.reply; 
                
                setChatHistory([...newHistory, { role: 'ai', text: aiReply }]);
            }
        } catch (error) {
            console.error("Lỗi kết nối:", error);
            setChatHistory([...newHistory, { role: 'ai', text: "Lỗi: Không thể kết nối đến server!" }]);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ padding: '20px', maxWidth: '600px', margin: '0 auto' }}>
            <h2>Gemini AI Chat</h2>
            <div style={{ border: '1px solid #ccc', height: '400px', overflowY: 'scroll', padding: '10px', marginBottom: '10px' }}>
                {chatHistory.map((item, index) => (
                    <div key={index} style={{ textAlign: item.role === 'user' ? 'right' : 'left', margin: '10px 0' }}>
                        <div style={{ 
                            display: 'inline-block',
                            background: item.role === 'user' ? '#007bff' : '#eee', 
                            color: item.role === 'user' ? 'white' : 'black',
                            padding: '0px 12px', borderRadius: '10px',
                            textAlign: 'left' // Đảm bảo text markdown căn lề trái bên trong bubble
                        }}>
                            {/* Ép kiểu về string để an toàn tuyệt đối cho ReactMarkdown */}
                            <ReactMarkdown>{String(item.text)}</ReactMarkdown>
                        </div>
                    </div>
                ))}
                {loading && <p><i>AI đang trả lời...</i></p>}
            </div>
            
            <div style={{ display: 'flex', gap: '10px' }}>
                <input 
                    type="text" 
                    value={message} 
                    onChange={(e) => setMessage(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && handleSend()}
                    style={{ flex: 1, padding: '10px' }} 
                    placeholder="Nhập câu hỏi..."
                    disabled={loading}
                />
                <button 
                    onClick={handleSend} 
                    style={{ padding: '10px 20px', cursor: 'pointer' }}
                    disabled={loading}
                >
                    {loading ? '...' : 'Gửi'}
                </button>
            </div>
        </div>
    );
};

export default ChatAi;