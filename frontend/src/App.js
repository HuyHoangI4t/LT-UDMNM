import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import './scss/style.scss';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import ChatAi from './components/ChatAi';
import ChatLogs from './components/ChatLogs';
import Header from './components/Header';

function App() {
  return (
    <div className="App">
      <Header />
      <Router>
        <Routes>
          <Route path="/" element={<ChatAi />} />
          <Route path="/chat-bot" element={<ChatAi />} />
          <Route path="/chat-logs" element={<ChatLogs />} />
        </Routes>
      </Router>
    </div>
  );
}

export default App;