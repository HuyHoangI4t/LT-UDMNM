import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import 'bootstrap/dist/css/bootstrap.min.css';
import ChatAi from './components/ChatAi';
import Header from './components/Header';
import Admin from './components/Admin';
import { AuthProvider } from './contexts/AuthContext';
// import Footer from './components/Footer';

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <div className="App">
          <Header />
          <Routes>
            <Route path="/" element={<ChatAi />} />
            <Route path="/admin" element={<Admin />} />
          </Routes>
          {/* <Footer /> */}
        </div>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
